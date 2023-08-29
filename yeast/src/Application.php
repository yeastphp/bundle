<?php

namespace Yeast;


use DI\Container;
use DI\ContainerBuilder;
use Yeast\Config\EmptyConfig;


abstract class Application
{
    const CONFIG = EmptyConfig::class;

    private Container $container;

    protected function getContainer(): Container
    {
        return $this->container;
    }

    public function __construct(protected Kernel $kernel)
    {
        $this->container = $this->kernel->getContainer();
    }

    public static function buildContainer(ContainerBuilder $builder, Kernel $kernel): void
    {
    }

    public function load(): void
    {
    }
}