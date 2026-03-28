<?php

namespace App\Services;

use Illuminate\Routing\Router;

/**
 * AddonLoader
 *
 * Runtime loader for enabled addons. In V1 we only auto-load addon routes,
 * keeping bootstrap logic simple and safe.
 */
class AddonLoader
{
    /**
     * Register all enabled addon routes.
     */
    public static function registerRoutes(Router $router): int
    {
        $loaded = 0;

        foreach (AddonManager::enabled() as $addon) {
            if (self::loadAddonRoutes($router, $addon)) {
                $loaded++;
            }
        }

        return $loaded;
    }

    /**
     * @param object $addon
     */
    public static function loadAddonRoutes(Router $router, object $addon): bool
    {
        $routesPath = AddonManager::getRoutesPath((string) $addon->slug);

        if ($routesPath === null) {
            return false;
        }

        try {
            CatminEventBus::dispatch(CatminEventBus::ADDON_BOOTING, [
                'addon' => [
                    'slug' => (string) $addon->slug,
                    'name' => (string) ($addon->name ?? $addon->slug),
                    'version' => (string) ($addon->version ?? ''),
                ],
            ]);

            require $routesPath;

            CatminEventBus::dispatch(CatminEventBus::ADDON_BOOTED, [
                'addon' => [
                    'slug' => (string) $addon->slug,
                    'name' => (string) ($addon->name ?? $addon->slug),
                    'version' => (string) ($addon->version ?? ''),
                ],
            ]);

            return true;
        } catch (\Throwable $e) {
            \Log::warning("Failed to load routes for addon {$addon->slug}: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function discoverAddons(): array
    {
        return AddonManager::all()
            ->map(fn ($addon) => [
                'type' => 'addon',
                'slug' => $addon->slug,
                'name' => $addon->name,
                'directory' => $addon->directory,
                'version' => $addon->version ?? 'unknown',
                'enabled' => (bool) ($addon->enabled ?? false),
                'requires_core' => (bool) ($addon->requires_core ?? true),
                'depends_modules' => (array) ($addon->depends_modules ?? []),
            ])
            ->values()
            ->toArray();
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function getRoutesInfo(): array
    {
        $info = [];

        foreach (AddonManager::enabled() as $addon) {
            $missing = AddonManager::missingStructure($addon);
            $routesPath = AddonManager::getRoutesPath((string) $addon->slug);
            $dependencyCheck = AddonManager::canEnable((string) $addon->slug);

            $info[(string) $addon->slug] = [
                'type' => 'addon',
                'has_routes' => $routesPath !== null,
                'routes_path' => $routesPath,
                'missing_structure' => $missing,
                'depends_modules' => (array) ($addon->depends_modules ?? []),
                'missing_modules' => $dependencyCheck['missing'],
            ];
        }

        return $info;
    }
}
