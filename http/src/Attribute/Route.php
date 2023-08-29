<?php

namespace Yeast\Http\Attribute;

use Attribute;


#[Attribute(Attribute::TARGET_FUNCTION | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Route {
    public array $method;
    public ?string $controller = null;
    public ?string $prefix = "";
    public array $attributes = [];

    /**
     * @param  string|null  $path
     * @param  array[]|string  $method
     * @param  string[]  $middleware
     */
    public function __construct(public ?string $path = null, array|string $method = "GET", public array $middleware = []) {
        if (is_string($method)) {
            $this->method = [$method];
        } else {
            $this->method = $method;
        }
    }

    public function resolveFullPath(): string {
        return ($this->prefix ?: "") . '/' . ltrim($this->path ?: '', '/');
    }

    public function getAttribute(string $name, mixed $default = null): mixed {
        return $this->attributes[$name] ?? $default;
    }

    public function setAttribute(string $name, mixed $value): void
    {
        $this->attributes[$name] = $value;
    }
}