<?php

namespace Yeast;

use DI\Container;
use DI\ContainerBuilder;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;


class Twig extends ModuleBase {
    public const NAME = "twig";

    public function __construct(private Environment $environment) {
    }

    public static function buildContainer(ContainerBuilder $builder, Kernel $kernel): void {
        $builder->addDefinitions(
          [
            Environment::class => function(Container $container) {
                $kernel = $container->get(Kernel::class);

                return new Environment($container->get(FilesystemLoader::class), ['debug' => $kernel->isDebug(), 'cache' => $kernel->getCacheDir() . '/twig']);
            },
            FilesystemLoader::class => function(Container $container) {
                $kernel = $container->get(Kernel::class);

                return new FilesystemLoader([$kernel->getApplicationDir() . '/views'], $kernel->getApplicationDir() . '/views');
            },
          ]
        );
    }

    public function getEnvironment(): Environment {
        return $this->environment;
    }
}