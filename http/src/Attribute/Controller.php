<?php

namespace Yeast\Http\Attribute;

use Attribute;
use Psr\Http\Server\MiddlewareInterface;


#[Attribute(Attribute::TARGET_CLASS)]
class Controller {
    public string $prefix;
    public array $attributes = [];
    public array $middlewares = [];

    public function __construct(?string $prefix = null) {
        $this->prefix = $prefix ? '/' . (ltrim($prefix, '/')) : "";
    }

    public function addCustomAttribute(string $attributeName, object $attribute) {
        if ( ! isset($this->attributes[$attributeName])) {
            $this->attributes[$attributeName] = [];
        }

        $this->attributes[$attributeName][] = $attribute;
    }

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