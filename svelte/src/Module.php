<?php

namespace Yeast\Svelte;

use DI\Container;
use DI\ContainerBuilder;
use Twig\Environment;
use Yeast\Kernel;
use Yeast\ModuleBase;

use Yeast\Svelte\Renderer\NodeJs;
use Yeast\Svelte\Twig\SvelteExtension;

use function DI\decorate;
use function DI\get;


class Module extends ModuleBase
{
    public static function buildContainer(ContainerBuilder $builder, Kernel $kernel): void
    {
        $builder->addDefinitions([
          NodeJs::class => fn(Container $container) => new NodeJs($kernel->getApplicationDir() . '/frontend/node_modules'),

          Renderer::class => get(NodeJs::class),

          Environment::class => decorate(function (Environment $previous, Container $container) use ($kernel) {
              $previous->addExtension(new SvelteExtension($container->get(Renderer::class), $kernel->getApplicationDir()));

              return $previous;
          }),
        ]);
    }

}