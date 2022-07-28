<?php

namespace Yeast;

class HomeCooking {
    private static bool $enabled = false;
    private static ?Kernel $kernel = null;

    public static function isEnabled(): bool {
        return self::$enabled;
    }

    public static function ensureEnabled(): void {
        if ( ! self::$enabled) {
            throw new \RuntimeException("Home cooking not enabled, can't use Home Cooking functions");
        }
    }

    public static function getKernel(): Kernel {
        self::ensureEnabled();

        if (self::$kernel === null) {
            throw new \RuntimeException("No kernel registered");
        }

        return self::$kernel;
    }

    public static function enable(Kernel $kernel) {
        if (self::$kernel !== null) {
            throw new \RuntimeException("HomeCooking already enabled for application " . get_class($kernel->getApplication()));
        }

        self::$enabled = true;
        self::$kernel  = $kernel;

        /** @var class-string<ModuleBase> $module */
        foreach ($kernel->getResolvedModules() as $module) {
            if (($module)::HOME_COOKING !== null) {
                includeFileOnce(($module)::HOME_COOKING);
            }
        }

        foreach ($kernel->getModules() as $module) {
            $module->enableHomeCooking();
        }
    }
}