<?php

namespace Yeast\Http\Attribute\Request;

use Psr\Http\Message\ServerRequestInterface;


interface ParameterResolver {
    public function setParameterName(string $name): void;

    public function resolve(ServerRequestInterface $request): mixed;
}