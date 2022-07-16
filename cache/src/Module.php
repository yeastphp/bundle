<?php

namespace Yeast\Cache;

use DI\ContainerBuilder;
use Yeast\Cache\Config\CacheConfig;
use Yeast\Kernel;
use Yeast\ModuleBase;

use function DI\autowire;


class Module extends ModuleBase {
    public const NAME = 'cache';

    public const CONFIG = CacheConfig::class;

    private bool $disabled = false;

    public function isDisabled(): bool {
        return $this->disabled;
    }

    public static function buildContainer(ContainerBuilder $builder, Kernel $kernel): void {
        $builder->addDefinitions(
          [
            CacheFactory::class => autowire(),
          ]
        );
    }

    /**
     * @param  CacheConfig  $config
     *
     * @return void
     */
    public function loadConfig(?object $config): void {
        if ($config->disable) {
            $this->disabled = $config->disable;
        }
    }
}