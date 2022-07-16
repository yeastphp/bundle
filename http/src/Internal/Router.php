<?php

namespace Yeast\Http\Internal;

use FastRoute\Dispatcher;
use Yeast\Http\Internal\Action\ControllerAction;
use Yeast\Http\Internal\Action\FunctionAction;
use Yeast\Http\Internal\Router\RouteContext;

use function Yeast\includeFileOnce;


class Router {
    public function __construct(
      private Dispatcher $dispatcher,
      private array $actions,
      private array $controllers,
      private array $routes,
    ) {
    }

    public function resolve(string $method, string $path): array {
        $data = $this->dispatcher->dispatch($method, $path) + [null, null, null];

        if ($data[0] === Dispatcher::FOUND) {
            [$routeId, $action] = $data[1];
            $route = $this->routes[$routeId];

            $actionData = $this->actions[$action];

            $controller = null;
            if ($actionData instanceof ControllerAction) {
                $controller = $this->controllers[$actionData->controller] ?? null;
            }

            if ($actionData instanceof FunctionAction) {
                includeFileOnce($actionData->file);
            }

            $routeContext = new RouteContext($actionData, $controller, $data[2], $route);

            return [Dispatcher::FOUND, $routeContext];
        }

        return $data;
    }
}