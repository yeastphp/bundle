<?php

namespace Yeast;

use DI\ContainerBuilder;


/**
 * @template T
 */
abstract class ModuleBase {
    /** @var class-string<T>|null */
    public const CONFIG = null;
    /** @var string */
    public const NAME = null;

    public static function buildContainer(ContainerBuilder $builder, Kernel $kernel): void {
    }

    /**
     * @param  T|null  $config
     */
    public function loadConfig(?object $config): void {
    }

    public static function getDependencies(): array {
        return [];
    }

    public static function hasBoot(): bool {
        return false;
    }

    public function boot() {
    }

    public function enableHomeCooking() {
    }
}