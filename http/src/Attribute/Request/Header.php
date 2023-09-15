<?php

namespace Yeast\Http\Attribute\Request;

use Attribute;
use Psr\Http\Message\RequestInterface;


#[Attribute(Attribute::TARGET_PARAMETER)]
class Header implements ParameterResolver {
    public function __construct(
      public ?string $name,
      public bool $all = false,
      public bool $required = false,
      public mixed $default = null,
      public bool $lines = false,
    ) {
    }

    public function resolve(RequestInterface $request): mixed {
        if ($this->all) {
            return $request->getHeaders();
        }

        if ($request->hasHeader($this->name)) {
            return $this->lines ? $request->getHeader($this->name) : $request->getHeaderLine($this->name);
        }

        if ( ! $this->required) {
            return $this->default;
        }

        throw new \RuntimeException("Missing Query parameter " . $this->key);
    }

    public function setParameterName(string $name): void {
        $this->name ??= $name;
    }

    public function setParameterType(string $type): void
    {
        // TODO: Implement setParameterType() method.
    }
}