<?php

namespace Yeast\Svelte\Twig;

use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Yeast\Svelte\Renderer;


class SvelteExtension extends AbstractExtension
{
    public function __construct(private readonly Renderer $renderer, private readonly string $componentDir)
    {
    }

    public function getFunctions()
    {
        return [
          new TwigFunction('svelte', function (Environment $environment, $name, $props = [], $context = []) {
              $res = $this->renderer->render($this->componentDir . '/' . $name, $props, $context);

              return $res->html . '<style>' . htmlentities($res->css['code']) . '</style>';
          }, ['needs_environment' => true, 'is_safe' => ['html']]),
        ];
    }
}