<?php

namespace Yeast\Http\Internal\Action;


use Yeast\Http\Internal\Action;


class FunctionAction extends Action {
    public function __construct(public string $function, public string $file) {
    }

    public function getName(): string {
        $pos = strrpos($this->function, '\\');

        if ($pos === false) {
            $pos = 0;
        } else {
            $pos += 1;
        }

        return substr($this->function, $pos);
    }

    public function __toString(): string {
        return $this->function;
    }
}