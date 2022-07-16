<?php

namespace Yeast\Http\Attribute;

use Attribute;
use Yeast\Http\Processor\ActionProcessor;


#[Attribute(Attribute::TARGET_CLASS)]
class ActionAttribute {
    /**
     * @param  class-string<ActionProcessor>|null  $processor
     */
    public function __construct(public ?string $processor = null) {
    }
}