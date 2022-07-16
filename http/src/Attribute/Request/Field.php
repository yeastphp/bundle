<?php

namespace Yeast\Http\Attribute\Request;

use Attribute;
use Psr\Http\Message\ServerRequestInterface;


#[Attribute(Attribute::TARGET_PARAMETER)]
class Field implements ParameterResolver {
    public function __construct(
      public ?string $field = null,
      public bool $all = false,
      public bool $required = false,
      public mixed $default = null,
    ) {
    }

    public function setParameterName(string $name): void {
        $this->field ??= $name;
    }

    public function resolve(ServerRequestInterface $request): mixed {
        $params = $request->getQueryParams();

        if ($this->all) {
            return $params;
        }

        if (array_key_exists($this->field, $params)) {
            return $params[$this->field];
        }

        if ( ! $this->required) {
            return $this->default;
        }

        throw new \RuntimeException("Missing field " . $this->field);
    }
}