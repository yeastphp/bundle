<?php

namespace Yeast\Http\Internal;

use AppendIterator;
use Closure;
use DI\Container;
use Psr\Log\LoggerInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use RegexIterator;
use Yeast\Http\Attribute\ActionAttribute;
use Yeast\Http\Attribute\Controller;
use Yeast\Http\Attribute\ControllerAttribute;
use Yeast\Http\Attribute\Request\ParameterResolver;
use Yeast\Http\Attribute\Route;
use Yeast\Http\Internal\Action\ControllerAction;
use Yeast\Http\Internal\Action\FunctionAction;
use Yeast\Http\Module;
use Yeast\Http\Mount;
use Yeast\Http\MountType;
use Yeast\Http\Processor\ActionProcessor;
use Yeast\Http\Processor\ControllerProcessor;
use Yeast\Kernel;

use function FastRoute\cachedDispatcher;


class RouteCollector {
    private array $routes = [];

    private array $controllers = [];
    private array $actions = [];

    private array $processors = [];

    private Closure $includeOnce;

    public function __construct(private Kernel $kernel, private LoggerInterface $logger, private Container $container, private Module $module) {
        $this->includeOnce = function(string $file) {
            $this->logger->debug('Force including ' . $file);
            include_once $file;
        };
    }

    public function getRouteCacheFile(): string {
        $cacheDir = $this->kernel->getCacheDir();

        return $cacheDir . '/router.cache.php';
    }

    public function getActionCacheFile(): string {
        $cacheDir = $this->kernel->getCacheDir();

        return $cacheDir . '/action.cache.phps';
    }

    public function getMountsCacheFile(): string {
        $cacheDir = $this->kernel->getCacheDir();

        return $cacheDir . '/mounts.cache.txt';
    }

    public function hasValidCache(): bool {
        $routeCacheFile  = $this->getRouteCacheFile();
        $actionCacheFile = $this->getActionCacheFile();
        $mountsCacheFile = $this->getMountsCacheFile();

        if ( ! file_exists($routeCacheFile) || ! file_exists($actionCacheFile) || ! file_exists($mountsCacheFile)) {
            return false;
        }

        if ($this->kernel->isProduction()) {
            return true;
        }

        $mountCache = unserialize(file_get_contents($mountsCacheFile));

        if ($mountCache['hash'] !== $this->module->getMountHash()) {
            return false;
        }

        $lastCache = min(filemtime($actionCacheFile) ?: -1, filemtime($routeCacheFile) ?: -1);

        foreach ($mountCache['dirs'] as $directory) {
            if (file_exists($directory) && (filemtime($directory) ?: 0) > $lastCache) {
                return false;
            }
        }

        return true;
    }

    public function collect() {
        $this->logger->info('Collecting routes from attributes');

        foreach ($this->module->getMounts() as $mount) {
            if ($mount->type == MountType::HANDLER) {
                $this->collectHandlers($mount);
            }

            if ($mount->type == MountType::CONTROLLER) {
                $this->collectControllers($mount);
            }
        }

        if (file_exists($this->getRouteCacheFile())) {
            unlink($this->getRouteCacheFile());
        }

        $this->writeCache();
    }

    public function build(): Router {
        // If we have cache we don't have to collect because the callable in the cachedDispatcher function never gets called
        if ( ! $this->hasValidCache()) {
            $this->collect();
        }

        ['actions' => $actions, 'controllers' => $controllers, 'routes' => $routes] = unserialize(file_get_contents($this->getActionCacheFile()));

        $dispatcher = cachedDispatcher(function(\FastRoute\RouteCollector $collector) {
            foreach ($this->routes as $id => [$route, $action]) {
                $collector->addRoute($route->method, $route->resolveFullPath(), [$id, $action->__toString()]);
            }
        }, [
             'cacheFile' => $this->getRouteCacheFile(),
           ]);

        return new Router($dispatcher, $actions, $controllers, $routes);
    }


    public function collectControllers(Mount $mount) {
        $phpFiles = $this->getMountPhpIterator($mount);

        foreach ($phpFiles as $phpFile) {
            ($this->includeOnce)($phpFile);
        }

        $classes = get_declared_classes();

        foreach ($classes as $class) {
            $found = false;
            foreach ($mount->namespaces as [$_, $namespace]) {
                if (str_starts_with(strtolower($class), $namespace)) {
                    $found = true;
                    break;
                }
            }

            if ( ! $found) {
                continue;
            }

            $reflection = new ReflectionClass($class);
            $customAttr = $reflection->getAttributes(Controller::class);

            // I'm sure this is a good idea in some sense, however with symlinks and all this tends to give problems
            //
            //  $found = false;
            //
            // foreach ($mount->directories as $directory) {
            //     if (str_starts_with($reflection->getFileName(), $directory)) {
            //         $found = true;
            //         break;
            //     }
            // }

            if (count($customAttr) === 0) {
                continue;
            }

            $this->logger->debug('Adding controller ' . $class);

            /** @var Controller $controller */
            $controller = $customAttr[0]->newInstance();

            $customAttr = $reflection->getAttributes();

            $processors = [];

            foreach ($customAttr as $attr) {
                $attrReflection = new ReflectionClass($attr->getName());
                $controllerAttr = $attrReflection->getAttributes(ControllerAttribute::class);
                if (count($controllerAttr) === 0) {
                    continue;
                }

                /** @var ControllerAttribute $controllerAttribute */
                $controllerAttribute = $controllerAttr[0]->newInstance();
                if ($controllerAttribute->processor !== null) {
                    $processors[$attr->getName()] = $controllerAttribute->processor;
                }

                $controller->addCustomAttribute($attr->getName(), $attr->newInstance());
            }

            // TODO: do something with the controller
            $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

            $actions = [];
            foreach ($methods as $method) {
                $attrs = $method->getAttributes(Route::class);
                if (count($attrs) === 0) {
                    continue;
                }

                $action = new ControllerAction($class, $method->getName());

                foreach ($attrs as $attr) {
                    /** @var Route $route */
                    $route             = $attr->newInstance();
                    $route->controller = $class;
                    $route->prefix     = $controller->prefix;
                    $this->addRoute($mount, $route, $action);
                }

                $this->setActionAttributes($method, $action);

                $this->actions[(string)$action] = $action;
                $actions[]                      = $action;
            }

            $this->controllers[$class] = $controller;

            foreach ($processors as $attribute => $processor) {
                /** @var ControllerProcessor $proc */
                $proc = $this->getProcessor($processor);
                foreach ($controller->attributes[$attribute] as $attribute) {
                    $proc->process($attribute, $actions);
                }
            }
        }
    }

    private function getMountPhpIterator(Mount $mount): \Iterator {
        $mount->resolveDirectories();
        $append = new AppendIterator();

        foreach ($mount->directories as $directory) {
            if ( ! is_dir($directory)) {
                continue;
            }

            $dir   = new RecursiveDirectoryIterator($directory);
            $iter  = new RecursiveIteratorIterator($dir);
            $regex = new RegexIterator($iter, '/^.+\.php$/i');

            $append->append($regex);
        }

        return $append;
    }

    public function collectHandlers(Mount $mount) {
        $this->logger->debug('Adding function actions');

        $phpFiles = $this->getMountPhpIterator($mount);

        foreach ($phpFiles as $phpFile) {
            ($this->includeOnce)($phpFile);
        }

        $funcs = get_defined_functions()["user"] ?? [];

        foreach ($funcs as $func) {
            $found = false;
            foreach ($mount->namespaces as [$_, $namespace]) {
                if (str_starts_with(strtolower($func), $namespace)) {
                    $found = true;
                    break;
                }
            }

            if ( ! $found) {
                continue;
            }

            $reflection = new ReflectionFunction($func);
            $attrs      = $reflection->getAttributes(Route::class);

            // I'm sure this is a good idea in some sense, however with symlinks and all this tends to give problems
            //
            //  $found = false;
            //
            // foreach ($mount->directories as $directory) {
            //     if (str_starts_with($reflection->getFileName(), $directory)) {
            //         $found = true;
            //         break;
            //     }
            // }

            if (count($attrs) === 0) {
                continue;
            }

            $action = new FunctionAction($func, $reflection->getFileName());

            foreach ($attrs as $attr) {
                /** @var Route $route */
                $route = $attr->newInstance();
                $this->addRoute($mount, $route, $action);
            }

            $this->setActionAttributes($reflection, $action);


            $this->actions[(string)$action] = $action;
        }
    }

    private function addRoute(Mount $mount, Route $route, Action $action) {
        if ($route->path === null) {
            $route->path = strtolower($action->getName());
        }

        $route->path = '/' . ltrim($mount->prefix . $route->path, '/');

        $fullPath = $route->resolveFullPath();
        $this->logger->debug('Adding action ' . $action . ' with route ' . $fullPath . ' (' . implode(", ", (array)$route->method) . ')');
        $this->routes[]   = [$route, $action];
        $action->routes[] = $action;
    }

    private function getProcessor(string $processor) {
        if ( ! isset($this->processors[$processor])) {
            $this->processors[$processor] = $this->container->get($processor);
        }

        return $this->processors[$processor];
    }

    private function writeCache() {
        $routes = [];

        foreach ($this->routes as $id => [$route, $_]) {
            $routes[$id] = $route;
        }

        $data = [
          'actions'     => $this->actions,
          'controllers' => $this->controllers,
          'routes'      => $routes,
        ];

        $action = $this->getActionCacheFile();
        file_put_contents($action, serialize($data));

        $dirs = [];

        foreach ($this->module->getMounts() as $mount) {
            foreach ($mount->directories as $directory) {
                $dirs[$directory] = true;
            }
        }

        file_put_contents($this->getMountsCacheFile(), serialize(['hash' => $this->module->getMountHash(), 'dirs' => array_keys($dirs)]));
    }

    private function setActionAttributes(ReflectionFunctionAbstract $reflection, Action $action): void {
        $customAttr = $reflection->getAttributes();

        foreach ($customAttr as $attr) {
            $attrReflection = new ReflectionClass($attr->getName());
            $actionAttr     = $attrReflection->getAttributes(ActionAttribute::class);
            if (count($actionAttr) === 0) {
                continue;
            }

            /** @var ActionAttribute $actionAttribute */
            $actionAttribute = $actionAttr[0]->newInstance();

            if ( ! isset($action->attributes[$attr->getName()])) {
                $action->attributes[$attr->getName()] = [];
            }

            $attributeObject                        = $attr->newInstance();
            $action->attributes[$attr->getName()][] = $attributeObject;

            if ($actionAttribute->processor !== null) {
                /** @var ActionProcessor $processor */
                $processor = $this->getProcessor($actionAttribute->processor);
                $processor->process($attributeObject, $action);
            }
        }

        foreach ($reflection->getParameters() as $parameter) {
            foreach ($parameter->getAttributes() as $attribute) {
                if (is_subclass_of($attribute->getName(), ParameterResolver::class)) {
                    /** @var ParameterResolver $param */
                    $param = $attribute->newInstance();
                    $param->setParameterName($parameter->getName());

                    $action->parameters[$parameter->getName()] = $param;
                    continue 2;
                }
            }
        }
    }
}