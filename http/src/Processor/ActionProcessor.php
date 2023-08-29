<?php

namespace Yeast\Http\Processor;

use Yeast\Http\Internal\Action;


/**
 * @template T
 */
interface ActionProcessor {
    /**
     * @param  T[]  $attribute
     * @param  Action  $action
     *
     * @return mixed
     */
    function process(array $attribute, Action $action);
}