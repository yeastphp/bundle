<?php

namespace Yeast\Http\HomeCooking;

use JetBrains\PhpStorm\ExpectedValues;


function json(mixed $input, #[ExpectedValues(flags: [JSON_HEX_AMP, JSON_HEX_APOS, JSON_HEX_QUOT, JSON_HEX_TAG, JSON_NUMERIC_CHECK, JSON_PRETTY_PRINT, JSON_FORCE_OBJECT, JSON_THROW_ON_ERROR, JSON_UNESCAPED_UNICODE, JSON_UNESCAPED_SLASHES])] int $flags = 0, int $depth = 512): void {
    header('Content-Type: application/json', replace: true);
    echo json_encode($input, $flags, $depth) . "\n";
}