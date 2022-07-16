<?php

namespace Yeast\Twig\HomeCooking;

use Twig\Environment;
use Twig\TemplateWrapper;
use Yeast\Twig;

use function Yeast\HomeCooking\kernel;


function twig(): Environment {
    return kernel()->module(Twig::class)->getEnvironment();
}

function render(string|TemplateWrapper $filename, array $context = []): string {
    return twig()->render($filename, $context);
}

function display(string|TemplateWrapper $filename, array $context = []): void {
    twig()->display($filename, $context);
}