<?php

namespace Yeast\Http\Attribute;

use Attribute;
use Psr\Http\Server\MiddlewareInterface;


#[Attribute(Attribute::TARGET_CLASS)]
class Controller {
    public string $prefix;
    public array $attributes = [];

    public function __construct(?string $prefix = null, public array $middleware = []) {
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
        if (in_array($middleware, $this->middleware)) {
            return;
        }

        $this->middleware[] = $middleware;
    }
}