<?php

namespace Yeast\Demo\Attribute;

use Attribute;
use Yeast\Http\Attribute\ActionAttribute;


#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_FUNCTION), ActionAttribute]
class Guard {
    public function __construct(
      private string|array $role,
    ) {
    }
}