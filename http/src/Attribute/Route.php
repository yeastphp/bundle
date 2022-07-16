<?php

namespace Yeast\Http\Attribute;

use Attribute;


#[Attribute(Attribute::TARGET_FUNCTION | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Route {
    public array $method;
    public ?string $controller = null;
    public ?string $prefix = "";

    public function __construct(public ?string $path = null, array|string $method = "GET") {
        if (is_string($method)) {
            $this->method = [$method];
        } else {
            $this->method = $method;
        }
    }


    public function resolveFullPath(): string {
        return ($this->prefix ?: "") . '/' . ltrim($this->path ?: '', '/');
    }
}