<?php

namespace Yeast\Roller;

function iter_to_array(iterable $iterable, bool $preserveKeys = false): array {
    if (is_array($iterable)) {
        return $preserveKeys ? $iterable : array_values($iterable);
    }

    /** @noinspection PhpParamsInspection */
    return iterator_to_array($iterable, $preserveKeys);
}