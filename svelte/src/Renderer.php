<?php

namespace Yeast\Svelte;

interface Renderer
{
    public function isAvailable(): bool;

    public function render($component, array $props = [], array $context = []): RenderResult;
}