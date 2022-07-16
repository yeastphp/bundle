<?php

namespace Yeast\Config;

use Yeast\Loafpan\Visitor;
use Yeast\Loafpan\Visitor\ArrayVisitor;


class ExpandingVisitor extends ArrayVisitor {
    public function __construct(mixed $value, private array $root) {
        parent::__construct($value);
    }

    protected function enter(mixed $value): Visitor {
        if (is_string($value) && str_contains($value, '%')) {
            $value = $this->expandVariable($value);
        }

        return new ExpandingVisitor($value, $this->root);
    }

    protected function expandVariable(string $value): mixed {
        if ( ! preg_match_all(':%([^%]*)%:', $value, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            return $value;
        }

        if (count($matches) === 1 && strlen($matches[0][0][0]) === strlen($value)) {
            return $this->fetchReplacement($matches[0][1][0]);
        }

        $built      = "";
        $lastOffset = 0;

        foreach ($matches as $match) {
            $built      .= substr($value, $lastOffset, $match[0][1]);
            $built      .= $this->fetchReplacement($match[1][0]);
            $lastOffset = $match[0][1] + strlen($match[0][0]);
        }

        $built .= substr($value, $lastOffset);

        return $built;
    }

    protected function fetchReplacement(string $selector): mixed {
        if ($selector === "") {
            return "%";
        }

        $parts   = explode(".", $selector);
        $current = $this->root;

        foreach ($parts as $part) {
            $current = $current[$part] ?? null;
        }

        return $current;
    }
}