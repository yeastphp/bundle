<?php

namespace Yeast\Http\Attribute\Request;

use Attribute;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;


#[Attribute(Attribute::TARGET_PARAMETER)]
class Query implements ParameterResolver {
    public function __construct(
      public ?string $key = null,
      public bool $all = false,
      public bool $required = false,
      public mixed $default = null,
    ) {
    }

    public function resolve(ServerRequestInterface $request): mixed {
        $params = $request->getQueryParams();

        if ($this->all) {
            return $params;
        }

        if (array_key_exists($this->key, $params)) {
            return $params[$this->key];
        }

        if ( ! $this->required) {
            return $this->default;
        }

        throw new RuntimeException("Missing Query parameter " . $this->key);
    }

    public function setParameterName(string $name): void {
        $this->key ??= $name;
    }
}