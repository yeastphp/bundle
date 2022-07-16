<?php

namespace Yeast\Http\Processor;

use Yeast\Http\Attribute\Controller;
use Yeast\Http\Internal\Action;


interface ControllerProcessor {
    /**
     * @param  Controller  $controller
     * @param  Action[]  $actions
     *
     * @return mixed
     */
    public function process(Controller $controller, array $actions);
}