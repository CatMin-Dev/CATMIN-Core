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
    protected static ?object $appInstance = null;

    /**
     * @var array<string, bool>
     */
    protected static array $loadedPaths = [];

    public static function load(): void
    {
        self::resetForNewApplicationInstance();
        self::loadModuleHooks();
        self::loadAddonHooks();
    }

    private static function resetForNewApplicationInstance(): void
    {
        $currentApp = app();

        if (self::$appInstance === $currentApp) {
            return;
        }

        self::$appInstance = $currentApp;
        self::$loadedPaths = [];
    }

    private static function loadModuleHooks(): void
    {
        foreach (ModuleManager::enabled() as $module) {
            $hooksPath = ModuleManager::getHooksPath((string) $module->slug);
            if ($hooksPath !== null && File::exists($hooksPath)) {
                self::loadHooksFile($hooksPath);
            }
        }
    }

    private static function loadAddonHooks(): void
    {
        foreach (AddonManager::enabled() as $addon) {
            $hooksPath = AddonManager::getHooksPath((string) $addon->slug);
            if ($hooksPath !== null && File::exists($hooksPath)) {
                self::loadHooksFile($hooksPath);
            }
        }
    }

    private static function loadHooksFile(string $hooksPath): void
    {
        if (isset(self::$loadedPaths[$hooksPath])) {
            return;
        }

        require $hooksPath;
        self::$loadedPaths[$hooksPath] = true;
    }
}
