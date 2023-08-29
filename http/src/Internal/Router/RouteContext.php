<?php

namespace Yeast\Http\Internal\Router;

use Yeast\Http\Attribute\Controller;
use Yeast\Http\Attribute\Route;
use Yeast\Http\Internal\Action;


class RouteContext
{
    public function __construct(
      public Action $action,
      public ?Controller $controller,
      public array $params,
      public Route $route,
    ) {
    }

    /**
     * @template T
     *
     * @param  class-string<T>  $class
     *
     * @return T|null
     */
    public function getActionAttribute(string $class): ?object
    {
        return $this->action->attributes[$class][0] ?? null;
    }
}