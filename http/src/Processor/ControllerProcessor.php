<?php

namespace Yeast\Http\Processor;

use Yeast\Http\Attribute\Controller;
use Yeast\Http\Internal\Action;


/**
 * @template T
 */
interface ControllerProcessor
{
    /**
     * @param  T[]  $attributes
     * @param  Controller  $controller
     * @param  Action[]  $actions
     *
     * @return mixed
     */
    public function processController(array $attributes, Controller $controller, array $actions);
}