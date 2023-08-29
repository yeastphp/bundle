<?php

namespace Yeast\Svelte;

class RenderResult
{
    public function __construct(public string $head, public string $html, public array $css)
    {

    }
}