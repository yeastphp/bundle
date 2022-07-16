<?php

namespace Yeast\Http\Internal;


use Psr\Http\Server\MiddlewareInterface;
use Yeast\Http\Attribute\Request\ParameterResolver;
use Yeast\Http\Attribute\Route;


abstract class Action {
    /** @var array<string,ParameterResolver> */
    public array $parameters = [];
    /**
     * @var array<string,array>
     */
    public array $attributes = [];

    /** @var Route[] */
    public array $routes = [];

    /**
     * @var class-string<MiddlewareInterface>[]
     */
    public array $middlewares = [];

    public abstract function getName(): string;

    public abstract function __toString(): string;

    /**
     * @param  class-string<MiddlewareInterface>  $middleware
     *
     * @return void
     */
    public function addMiddleware(string $middleware): void {
        if (in_array($middleware, $this->middlewares)) {
            return;
        }

        $this->middlewares[] = $middleware;
    }
}