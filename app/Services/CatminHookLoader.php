<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

/**
 * CatminHookLoader
 *
 * Loads optional hooks.php files from enabled modules and addons.
 */
class CatminHookLoader
{
    public static function load(): void
    {
        self::loadModuleHooks();
        self::loadAddonHooks();
    }

    private static function loadModuleHooks(): void
    {
        foreach (ModuleManager::enabled() as $module) {
            $hooksPath = ModuleManager::getHooksPath((string) $module->slug);
            if ($hooksPath !== null && File::exists($hooksPath)) {
                require_once $hooksPath;
            }
        }
    }

    private static function loadAddonHooks(): void
    {
        foreach (AddonManager::enabled() as $addon) {
            $hooksPath = AddonManager::getHooksPath((string) $addon->slug);
            if ($hooksPath !== null && File::exists($hooksPath)) {
                require_once $hooksPath;
            }
        }
    }
}
