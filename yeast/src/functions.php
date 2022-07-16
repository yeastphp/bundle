<?php

namespace Yeast;

/**
 * like includeFile from Composer, but includeOnce instead
 *
 * @param  string  $name
 *
 * @return void
 */
function includeFileOnce(string $name) {
    include_once $name;
}