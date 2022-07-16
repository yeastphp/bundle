<?php

namespace Yeast\Roller;

/**
 * @template T
 */
interface Listable {
    /**
     * @param  T  $item
     */
    public function add($item): void;

    /**
     * @param  T  $item
     *
     * @return bool
     */
    public function remove($item): bool;
}