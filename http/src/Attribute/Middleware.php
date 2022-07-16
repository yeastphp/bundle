<?php

namespace Yeast\Http\Attribute;

#[\Attribute(\Attribute::TARGET_FUNCTION | \Attribute::TARGET_METHOD)]
class Middleware {
    public function __construct(string ...$middleware) {
    }
}