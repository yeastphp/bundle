<?php

namespace Yeast\Http\Internal\Action;

use Yeast\Http\Internal\Action;


class ControllerAction extends Action {
    public function __construct(public string $controller, public string $method) {
    }

    public function getName(): string {
        return $this->method;
    }

    public function __toString(): string {
        return $this->controller . '::' . $this->method;
    }
}