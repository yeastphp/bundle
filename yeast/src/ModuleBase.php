<?php

namespace Yeast;

use DI\ContainerBuilder;


/**
 * The base class for a custom module for Yeast, which allows for injection of boot logic, or expansion of the dependency container
 *
 * @template T
 */
abstract class ModuleBase {
    /**
     * The (optional) config class for this module, it should be a valid Loafpan unit,
     * This config will be loaded from %app-dir%/config/module/${static::NAME}.json, or if not found %app-dir%/config/module/${static::NAME}.yml
     *
     * It will then be exposed to the framework in the container as `module.${static::NAME}.config`
     *
     * @var class-string<T>|null
     */
    public const CONFIG = null;
    /**
     * The name of this module, as it should be referred to by the programmer, this is also used as part of the config name
     *
     * @var string|null
     */
    public const NAME = null;
    /**
     * The path to the home cooking file of this module
     *
     * @var string|null
     */
    public const HOME_COOKING = null;

    /**
     * This function will be called when creating the dependency container, and can be used to register new entries in the container, these entries can then be used by the application or other modules
     *
     * @param  ContainerBuilder  $builder
     * @param  Kernel  $kernel
     *
     * @return void
     */
    public static function buildContainer(ContainerBuilder $builder, Kernel $kernel): void {
    }

    /**
     * After the module is initialized by the DI container, this function will be called with the available config, if CONFIG is set to null, this will not be called
     *
     * @param  T|null  $config
     */
    public function loadConfig(?object $config): void {
    }

    /**
     * Which modules should be included when this module is used.
     * When e.g. creating a GraphQL module, one might require the HTTP module
     *
     * Modules should be returned by absolute class name e.g.
     *
     * ```php
     * return [Yeast\Http\Module::class];
     * ```
     *
     * @return array
     */
    public static function getDependencies(): array {
        return [];
    }

    /**
     * If this module has a boot function that should be used.
     * This boot function is called only once when the application boots
     *
     * @return bool
     */
    public static function hasBoot(): bool {
        return false;
    }

    /**
     * This function will be called when the application start, and can be used for early setup
     *
     * @return void
     */
    public function boot() {
    }

    /**
     * This function is called when the application enables "home cooking" mode, a mode which allows violation of best practices and makes assumptions about the runtime
     *
     * @return void
     */
    public function enableHomeCooking() {
    }
}