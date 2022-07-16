<?php

namespace Yeast\Http\Attribute;

use Attribute;
use Yeast\Http\Processor\ControllerProcessor;


#[Attribute(Attribute::TARGET_CLASS)]
class ControllerAttribute {
    /**
     * @param  class-string<ControllerProcessor>|null  $processor
     */
    public function __construct(public ?string $processor = null) {
    }
}