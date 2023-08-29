<?php

namespace Yeast\Http\Attribute\Request;

use Attribute;


/**
 * @template T of RequestResolver
 */
#[Attribute(Attribute::TARGET_CLASS)]
class RequestTargetResolver
{
    /**
     * @param  class-string<T>|null  $resolver
     */
    public function __construct(
      public ?string $resolver = null
    ) {
    }
}