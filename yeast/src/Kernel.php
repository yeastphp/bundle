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


final class Kernel {
    private Loafpan $loafpan;
    private YeastConfig $config;
    private array $configArray;

    private string $configDir;
    private string $applicationDir;
    private string $cacheDir;
    private Application $application;
    private Logger $logger;
    private ?Facet $currentFacet = null;
    private Container $container;
    /** @var array<class-string<ModuleBase>,ModuleBase> */
    private array $modules = [];

    /** @var array<class-string<ModuleBase>,ModuleBase> */
    private array $moduleConfigs = [];

    private bool $production = false;

    private function __construct(string $applicationDir, private string $applicationNamespace) {
        $appDir = realpath($applicationDir);
        if ($appDir === false) {
            throw new RuntimeException("Application directory by " . $applicationDir . " does not exist");
        }

        $this->applicationDir = $appDir;
        $this->configDir      = $appDir . '/config';
    }

    /**
     * @template A of Application
     * @template R of Runtime
     * @template F of Facet<R>
     *
     * @param  class-string<A>  $application
     * @param  class-string<F>  $facet
     *
     * @return R
     */
    public static function run(string $application, string $facet, string $applicationDirectory = ".", ?string $applicationNamespace = null, bool $production = false): Runtime {
        $kernel             = Kernel::create($application, $applicationDirectory, $applicationNamespace);
        $kernel->production = $production;

        return $kernel->runtime($facet);
    }

    public static function create(string $application, string $applicationDirectory, ?string $applicationNamespace = null): Kernel {
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
        $kernel->loadConfig();
        $kernel->resolveModules($application);
        $kernel->loadContainer($application);
        $kernel->loadModules();
        $kernel->enableHomeCooking();
        $kernel->loadApplication($application);

        return $kernel;
    }

    private function earlyBoot() {
        $this->createDefaultLogger();
        $this->logger->debug('Changing working directory to ' . $this->applicationDir);
        chdir($this->applicationDir);
    }

    private function createDefaultLogger() {
        $this->logger = new Logger('kernel');
        $this->logger->pushHandler(new ErrorLogHandler());
    }

    public function loadConfig() {
        $this->logger->debug('Loading config');
        $config = $this->loadConfigFile('yeast');

        $cacheDir = $this->getApplicationDir() . '/var/cache';

        // by pass loafpan because we load config's by loafpan
        if (isset($config['cache-dir'])) {
            $cacheDir = $config['cache-dir'];
        }

        $this->cacheDir = $cacheDir;

        $meta = [
          'app-dir' => $this->getApplicationDir(),
        ];

        $config['_meta'] = $meta;

        if ( ! is_dir($cacheDir . '/loafpan')) {
            mkdir($cacheDir . '/loafpan', recursive: true);
        }

        $this->loafpan     = new Loafpan($cacheDir . '/loafpan', casing: 'kebab-case');
        $this->config      = $this->loafpan->expandVisitor(YeastConfig::class, new ExpandingVisitor($config, $config));
        $this->configArray = $config;
        $this->config->setCacheDir($cacheDir);
        $this->logger->debug('Loaded config');
    }

    private function loadConfigFile(string $path): mixed {
        $fullPath = $this->configDir . '/' . $path;

        if (file_exists($fullPath . '.json')) {
            $configJson = file_get_contents($fullPath . '.json');

            return json_decode($configJson, true, flags: JSON_THROW_ON_ERROR);
        }

        if (file_exists($fullPath . '.yml')) {
            return Yaml::parseFile($fullPath . '.yml');
        }

        return [];
    }

    public function getApplicationDir(): string {
        return $this->applicationDir;
    }

    public function getApplicationNamespace(): string {
        return $this->applicationNamespace;
    }

    private function resolveModules(string $application) {
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

    private function resolveModule(string $name, array $requestedBy): string {
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
    private function loadContainer(string $application) {
        $this->logger->debug('Building container');

        $builder = new ContainerBuilder();

        if (extension_loaded('apcu')) {
            $builder->enableDefinitionCache('Yeast\\Container\\Cache');
        }

        $builder->useAttributes(true);
        $builder->useAnnotations(false);
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
            'logger.*'             => function(Container $container, Definition $definition) {
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
                $modules['module.' . $moduleName . '.config'] = factory(function(Container $container) use ($module) {
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
     * @return C
     */
    public function loadModuleConfig(string $module) {
        if (array_key_exists($module, $this->moduleConfigs)) {
            return $this->moduleConfigs[$module];
        }

        $configClass  = ($module)::CONFIG;
        $friendlyName = ($module)::NAME;

        $configData           = $this->loadConfigFile('module/' . $friendlyName);
        $configRoot           = $this->configArray;
        $configRoot['module'] = [$friendlyName => $configData];

        return $this->moduleConfigs[$module] = $this->loafpan->expandVisitor($configClass, new ExpandingVisitor($configData, $configRoot));
    }

    private function loadModules() {
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
    public function hasModule(string $module): bool {
        return isset($this->modules[$module]) || in_array($module, $this->config->resolvedModules, true);
    }


    /**
     * @template M of ModuleBase
     *
     * @param  class-string<M>  $module
     *
     * @return M
     */
    public function module(string $module): ModuleBase {
        if (isset($this->modules[$module])) {
            return $this->modules[$module];
        }

        if ( ! in_array($module, $this->config->resolvedModules)) {
            throw new RuntimeException("Module " . $module . ' is not registered');
        }

        $this->logger->debug('Loading module ' . $module);

        return $this->modules[$module] = $this->container->get($module);
    }

    private function enableHomeCooking() {
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
    private function loadApplication(string $application) {
        $this->logger->debug('Loading application ' . $application);
        $this->application = $this->container->get($application);
    }

    /**
     * @template R of Runtime
     * @template F of Facet<R>
     *
     * @param  class-string<F>  $facet
     *
     * @return R
     */
    public function runtime(string $facet): Runtime {
        return $this->facet($facet)->runtime();
    }

    /**
     * @template R of Runtime
     * @template F of Facet<R>
     *
     * @param  class-string<F>  $facet
     *
     * @return F
     */
    public function facet(string $facet): Facet {
        if ( ! class_exists($facet)) {
            throw new RuntimeException("Facet by name $facet couldn't be found");
        }

        if ($this->currentFacet === null) {
            $this->logger->debug('Loading facet ' . $facet . ' for app');
            $this->currentFacet = $this->container->get($facet);

            return $this->currentFacet;
        }

        if (is_a($this->currentFacet, $facet)) {
            return $this->currentFacet;
        }

        throw new RuntimeException("Facet of type " . get_class($this->currentFacet) . " already registered, can't use different facet of type " . $facet);
    }

    public function getConfigDir(): string {
        return $this->configDir;
    }

    public function getCacheDir(): string {
        return $this->cacheDir;
    }

    public function getApplication(): Application {
        return $this->application;
    }

    public function getModules(): array {
        return $this->modules;
    }

    public function isProduction(): bool {
        return $this->production;
    }

    public function isDebug(): bool {
        return ! $this->production;
    }

    public function getLogger(): Logger {
        return $this->logger;
    }

    private function getFacetConfig(string $name): array {
        return $this->loadConfigFile("facet/$name");
    }
}