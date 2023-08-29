<?php

namespace Yeast;

use DI\Container;
use DI\ContainerBuilder;
use DI\Definition\Definition;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\Yaml\Yaml;
use Yeast\Config\ExpandingVisitor;
use Yeast\Config\YeastConfig;
use Yeast\Loafpan\Loafpan;

use function DI\autowire;
use function DI\factory;
use function DI\get;
use function DI\value;


/**
 * The central kernel of yeast, controls loading of modules, configs and the container.
 */
final class Kernel
{
    private Loafpan $loafpan;
    private YeastConfig $config;
    private array $configArray;

    private string $configDir;
    private string $applicationDir;
    private string $cacheDir;
    private Application $application;
    private Logger $logger;
    private ?Facet $currentFacet = null;
    private ?string $currentFacetName = null;
    private Container $container;
    /** @var array<class-string<ModuleBase>,ModuleBase> */
    private array $modules = [];

    /** @var array<class-string<ModuleBase>,ModuleBase> */
    private array $moduleConfigs = [];

    private bool $production = false;

    private function __construct(string $applicationDir, private string $applicationNamespace)
    {
        $appDir = realpath($applicationDir);
        if ($appDir === false) {
            throw new RuntimeException("Application directory by " . $applicationDir . " does not exist");
        }

        $this->applicationDir = $appDir;
        $this->configDir      = $appDir . '/config';
    }

    /**
     * Create a kernel for your application, and spawn a runtime for a facet.
     *
     * This is a shortcut for
     * ```php
     * Kernel::create($applicationName, $applicationDirectory)->runtime($facet)
     * ```
     *
     * This is useful for e.g. http entry points or console entry points
     *
     * @template A of Application
     * @template R of Runtime
     * @template F of Facet<R>
     *
     * @param  class-string<A>  $application  The main application class
     * @param  class-string<F>  $facet  For which facet a runtime should be created
     * @param  string  $applicationDirectory  The application root directory, with e.g. your vendor and src directories
     * @param  ?string  $applicationNamespace  The namespace your application lives in, if null is used it will use the namespace your application class lives in
     * @param  bool  $production  If this is production
     *
     * @return R
     * @see Kernel::runtime
     *
     * @see Kernel::create
     */
    public static function run(string $application, string $facet, string $applicationDirectory = ".", ?string $applicationNamespace = null, bool $production = false): Runtime
    {
        $kernel             = Kernel::create($application, $applicationDirectory, $applicationNamespace);
        $kernel->production = $production;

        return $kernel->runtime($facet);
    }

    /**
     * Creates and prepares a new kernel for given application.
     *
     * This function will initiate the following steps:
     * - Load config
     * - Resolve modules to be used
     * - Load the DI container
     * - Load and boot modules
     * - Load the application
     *
     * @template A of Application
     *
     * @param  class-string<A>  $application  The main application class, should extend the class \Yeast\Application
     * @param  string  $applicationDirectory  The root directory of your app, where e.g. your vendor, config and src directory live
     * @param  string|null  $applicationNamespace  The namespace in which your application lives, if null is given it uses the namespace of your application (e.g. in the case of the application \My\Demo\Application, it will use the namespace \My\Demo)
     *
     * @return Kernel
     */
    public static function create(string $application, string $applicationDirectory, ?string $applicationNamespace = null): Kernel
    {
        if ($applicationNamespace === null) {
            $lastPos = strrpos($application, '\\');

            if ($lastPos === false) {
                $applicationNamespace = '';
            } else {
                $applicationNamespace = substr($application, 0, $lastPos);
            }
        }

        $kernel = new Kernel($applicationDirectory, $applicationNamespace);
        $kernel->earlyBoot();
        $kernel->loadConfig($application);
        $kernel->resolveModules($application);
        $kernel->loadContainer($application);
        $kernel->loadModules();
        $kernel->enableHomeCooking();
        $kernel->loadApplication($application);

        return $kernel;
    }

    private function earlyBoot(): void
    {
        $this->createDefaultLogger();
        $this->logger->debug('Changing working directory to ' . $this->applicationDir);
        chdir($this->applicationDir);
    }

    private function createDefaultLogger(): void
    {
        $this->logger = new Logger('kernel');
        $this->logger->pushHandler(new ErrorLogHandler());
    }

    /**
     * @param  class-string<Application>  $application
     */
    private function loadConfig(string $application): void
    {
        $this->logger->debug('Loading config');
        $config    = $this->loadConfigFile('yeast');
        $appConfig = $this->loadConfigFile('app');

        $cacheDir = $this->getApplicationDir() . '/var/cache';

        // by pass loafpan because we load config's by loafpan
        if (isset($config['cache-dir'])) {
            $cacheDir = $config['cache-dir'];
        }

        $this->cacheDir = $cacheDir;

        $meta = [
          'app-dir' => $this->getApplicationDir(),
        ];

        $config['app']  = $appConfig;
        $config['_meta'] = $meta;

        if ( ! is_dir($cacheDir . '/loafpan')) {
            mkdir($cacheDir . '/loafpan', recursive: true);
        }

        $this->loafpan = new Loafpan($cacheDir . '/loafpan', casing: 'kebab-case');

        $visitor = new ExpandingVisitor($config, $config);

        $this->config      = $this->loafpan->expandVisitor(YeastConfig::class, $visitor);
        $this->config->app = $this->loafpan->expandVisitor(($application)::CONFIG, new ExpandingVisitor($appConfig, $config));

        $this->configArray = $config;
        $this->config->setCacheDir($cacheDir);
        $this->logger->debug('Loaded config');
    }

    private function loadConfigFile(string $path): mixed
    {
        $fullPath = $this->configDir . '/' . $path;

        if (file_exists($fullPath . '.json')) {
            $configJson = file_get_contents($fullPath . '.json');

            return json_decode($configJson, true, flags: JSON_THROW_ON_ERROR);
        }

        if (file_exists($fullPath . '.yml')) {
            return Yaml::parseFile($fullPath . '.yml') ?: [];
        }

        return [];
    }

    public function getApplicationDir(): string
    {
        return $this->applicationDir;
    }

    public function getApplicationNamespace(): string
    {
        return $this->applicationNamespace;
    }

    private function resolveModules(string $application): void
    {
        $specifiedModules = $this->config->getSpecifiedModules();
        $wantedModules    = array_flip($specifiedModules);
        $resolvedModules  = [];
        $requestedBy      = [];

        foreach ($specifiedModules as $module) {
            $requestedBy[$module]   ??= [];
            $requestedBy[$module][] = '{Application: ' . $application . '}';

            $className = $this->resolveModule($module, $requestedBy[$module]);

            $resolvedModules[$className] = $className;
            $dependencies                = ($className)::getDependencies();

            foreach ($dependencies as $dependency) {
                $wantedModules[$dependency] = true;
                $requestedBy[$dependency]   ??= [];
                $requestedBy[$dependency][] = $className;
            }
        }

        $wantedModules = array_keys($wantedModules);

        while (($module = array_shift($wantedModules)) !== null) {
            if (isset($resolvedModules[$module]) || isset($resolvedModules[$module . "\\Module"])) {
                continue;
            }

            $className                   = $this->resolveModule($module, $requestedBy[$module]);
            $resolvedModules[$className] = $className;

            $dependencies = ($className)::getDependencies();

            foreach ($dependencies as $dependency) {
                $wantedModules[]            = $dependency;
                $requestedBy[$dependency]   ??= [];
                $requestedBy[$dependency][] = $className;
            }
        }

        $this->config->setResolvedModules(array_values($resolvedModules));
    }

    /**
     * @param  string  $name
     * @param  array  $requestedBy
     *
     * @return class-string<ModuleBase>
     */
    private function resolveModule(string $name, array $requestedBy): string
    {
        $expandedName = $name . "\\Module";
        /** @var class-string<ModuleBase> $className */
        $className = $name;
        if ( ! class_exists($name)) {
            $className = $expandedName;
            if ( ! class_exists($className)) {
                throw new RuntimeException("Couldn't find module $name (neither as $expandedName or $name) requested by [" . implode(", ", $requestedBy) . ']');
            }
        }

        if ( ! is_subclass_of($className, ModuleBase::class)) {
            throw new RuntimeException("Found module $name (as $className) but it's not a yeast module requested by [" . implode(", ", $requestedBy) . ']');
        }

        return $className;
    }

    /**
     * @template T of Application
     *
     * @param  class-string<T>  $application
     *
     * @return void
     */
    private function loadContainer(string $application): void
    {
        $this->logger->debug('Building container');

        $builder = new ContainerBuilder();

        if (extension_loaded('apcu')) {
            $builder->enableDefinitionCache('Yeast\\Container\\Cache');
        }

        $builder->useAttributes(true);
        $builder->useAutowiring(true);

        if ($this->production) {
            $builder->enableCompilation($this->cacheDir . '/container');
            $builder->writeProxiesToFile(true, $this->cacheDir . '/container/proxies');
        }

        $builder->addDefinitions(
          [
            'app'                  => get($application),
            $application           => autowire($application),
            Kernel::class          => value($this),
            Loafpan::class         => value($this->loafpan),
            LoggerInterface::class => get(Logger::class),
            Logger::class          => value($this->logger),
            'logger.*'             => function (Container $container, Definition $definition) {
                return $container->get(Logger::class)->withName(substr($definition->getName(), 7));
            },
          ]
        );

        $modules = [];

        foreach ($this->config->getResolvedModules() as $module) {
            $modules[$module] = autowire();


            if (($module)::CONFIG !== null) {
                $moduleName = ($module)::NAME;
                $modules[$module]->methodParameter('loadConfig', 0, get('module.' . $moduleName . '.config'));
                $modules['module.' . $moduleName . '.config'] = factory(function (Container $container) use ($module) {
                    $kernel = $container->get(Kernel::class);

                    return $kernel->loadModuleConfig($module);
                });
            }

            ($module)::buildContainer($builder, $this);
        }

        $builder->addDefinitions($modules);

        ($application)::buildContainer($builder, $this);

        $this->container = $builder->build();
    }

    /**
     * @template C
     * @template M of ModuleBase<C>
     *
     * @param  class-string<M>  $module
     *
     * @return C|null
     */
    public function loadModuleConfig(string $module): ?object
    {
        if (array_key_exists($module, $this->moduleConfigs)) {
            return $this->moduleConfigs[$module];
        }

        $configClass = ($module)::CONFIG;
        if ($configClass === null) {
            $this->moduleConfigs[$module] = null;

            return null;
        }

        $friendlyName = ($module)::NAME;

        $configData           = $this->loadConfigFile('module/' . $friendlyName);
        $configRoot           = $this->configArray;
        $configRoot['module'] = [$friendlyName => $configData];

        return $this->moduleConfigs[$module] = $this->loafpan->expandVisitor($configClass, new ExpandingVisitor($configData, $configRoot));
    }

    private function loadModules(): void
    {
        foreach ($this->config->resolvedModules as $module) {
            if (($module)::hasBoot()) {
                $this->module($module)->boot();
            }
        }
    }


    /**
     * @template M of ModuleBase
     *
     * @param  class-string<M>  $module
     *
     * @return bool
     */
    public function hasModule(string $module): bool
    {
        return isset($this->modules[$module]) || in_array($module, $this->config->resolvedModules, true);
    }


    /**
     * @template M of ModuleBase
     *
     * @param  class-string<M>  $module
     *
     * @return M
     */
    public function module(string $module): ModuleBase
    {
        if (isset($this->modules[$module])) {
            return $this->modules[$module];
        }

        if ( ! in_array($module, $this->config->resolvedModules)) {
            throw new RuntimeException("Module " . $module . ' is not registered');
        }

        $this->logger->debug('Loading module ' . $module);

        return $this->modules[$module] = $this->container->get($module);
    }

    private function enableHomeCooking(): void
    {
        if ($this->config->isHomeCooking()) {
            HomeCooking::enable($this);
        }
    }

    /**
     * @template A of Application
     *
     * @param  class-string<A>  $application
     *
     * @return void
     */
    private function loadApplication(string $application): void
    {
        $this->logger->debug('Loading application ' . $application);
        $this->application = $this->container->get($application);
        $this->application->load();
    }

    /**
     * Create a runtime for the given facet. This function fails if a different facet has already been used.
     *
     * @template R of Runtime
     * @template F of Facet<R>
     *
     * @param  class-string<F>  $facet
     *
     * @return R
     * @see Facet::runtime()
     * @see Kernel::facet()
     */
    public function runtime(string $facet): Runtime
    {
        return $this->facet($facet)->runtime();
    }

    /**
     * Returns a facet for this yeast application.
     * If there's no facet for this kernel yet, a new one is created.
     * When a facet already exists, it will either return the existing facet if it is the same as the requested facet. or throw an exception.
     *
     * Only one facet can be active in the span of application lifetime. (this is an artificial limitation and may be changed later.)
     *
     * @template R of Runtime
     * @template F of Facet<R>
     *
     * @param  class-string<F>  $facet  The class name of the requested facet.
     *
     * @return F
     * @see \Yeast\Facet
     */
    public function facet(string $facet): Facet
    {
        if ($this->currentFacet === null) {
            if ( ! class_exists($facet)) {
                throw new RuntimeException("Facet by name $facet couldn't be found");
            }

            $this->logger->debug('Loading facet ' . $facet . ' for app');
            $this->currentFacet     = $this->container->get($facet);
            $this->currentFacetName = $facet;

            return $this->currentFacet;
        }

        if ($this->currentFacetName === $facet) {
            return $this->currentFacet;
        }

        throw new RuntimeException("Facet of type " . get_class($this->currentFacet) . " already registered, can't use different facet of type " . $facet);
    }

    public function getConfigDir(): string
    {
        return $this->configDir;
    }

    public function getCacheDir(): string
    {
        return $this->cacheDir;
    }

    public function getApplication(): Application
    {
        return $this->application;
    }

    public function getModules(): array
    {
        return $this->modules;
    }

    public function isProduction(): bool
    {
        return $this->production;
    }

    public function isDebug(): bool
    {
        return ! $this->production;
    }

    public function getLogger(): Logger
    {
        return $this->logger;
    }

    private function getFacetConfig(string $name): array
    {
        return $this->loadConfigFile("facet/$name");
    }

    /**
     * Returns all the modules that have been resolved to be loaded
     *
     * @return array<class-string<ModuleBase>>
     */
    public function getResolvedModules(): array
    {
        return $this->config->getResolvedModules();
    }

    public function getContainer(): Container
    {
        return $this->container;
    }
}