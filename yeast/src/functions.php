<?php

namespace Yeast;

/**
 * like includeFile from Composer, but includeOnce instead
 *
 * Doing this in a function allows the php file not to escape scope, nor "peek" at variables from the context it's called
 *
 * @param  string  $name
 *
 * @return void
 */
function includeFileOnce(string $name) {
    include_once $name;
}