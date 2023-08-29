<?php

namespace Yeast\Http\Attribute\Request;

interface RequestResolver extends ParameterResolver
{
    public static function build(string $targetType): static;
}