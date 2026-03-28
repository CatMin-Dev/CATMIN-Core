<?php

namespace App\Services;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\File;

/**
 * ModuleLoader
 *
 * Loads and registers routes from enabled modules into the Laravel routing system.
 *
 * Usage:
 *   ModuleLoader::registerRoutes($router);
 *   ModuleLoader::discoverModules();
 */
class ModuleLoader
{
    /**
     * @var array<string, bool>
     */
    protected static array $loadedRoutes = [];

    /**
     * Register all enabled module routes
     *
     * @param Router $router
     * @return int Number of modules loaded
     */
    public static function registerRoutes(Router $router): int
    {
        if (!config('catmin.modules.auto_load', true) || !config('catmin.modules.auto_discover_routes', true)) {
            return 0;
        }

        $loaded = 0;
        $modules = ModuleManager::enabled();

        foreach ($modules as $module) {
            if (self::loadModuleRoutes($router, $module)) {
                $loaded++;
            }
        }

        return $loaded;
    }

    /**
     * Load routes for a specific module
     *
     * @param Router $router
     * @param object $module
     * @return bool
     */
    public static function loadModuleRoutes(Router $router, object $module): bool
    {
        $slug = (string) ($module->slug ?? '');
        if ($slug === '') {
            return false;
        }

        if (isset(self::$loadedRoutes[$slug])) {
            return true;
        }

        $routesPath = ModuleManager::getRoutesPath($slug);

        if (!$routesPath) {
            return false;
        }

        try {
            require_once $routesPath;
            self::$loadedRoutes[$slug] = true;
            return true;
        } catch (\Throwable $e) {
            \Log::warning("Failed to load routes for module {$slug}: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Discover all modules and return metadata
     *
     * @return array
     */
    public static function discoverModules(): array
    {
        return ModuleManager::all()
            ->map(fn ($module) => [
                'slug' => $module->slug,
                'name' => $module->name,
                'directory' => $module->directory,
                'version' => $module->version ?? 'unknown',
                'enabled' => $module->enabled,
                'depends' => $module->depends ?? [],
            ])
            ->toArray();
    }

    /**
     * Get routes information for enabled modules
     *
     * @return array
     */
    public static function getRoutesInfo(): array
    {
        $info = [];

        foreach (ModuleManager::enabled() as $module) {
            $routesPath = ModuleManager::getRoutesPath($module->slug);
            $info[$module->slug] = [
                'has_routes' => $routesPath !== null,
                'routes_path' => $routesPath,
            ];
        }

        return $info;
    }

    /**
     * Check module dependencies
     *
     * @param string $slug
     * @return array ['valid' => bool, 'missing' => array]
     */
    public static function checkDependencies(string $slug): array
    {
        $module = ModuleManager::find($slug);

        if (!$module) {
            return [
                'valid' => false,
                'missing' => [],
                'message' => "Module '{$slug}' not found",
            ];
        }

        $missing = [];
        $depends = $module->depends ?? [];

        foreach ((array) $depends as $dependency) {
            if (!ModuleManager::exists($dependency)) {
                $missing[] = $dependency;
            }
        }

        return [
            'valid' => count($missing) === 0,
            'missing' => $missing,
            'message' => count($missing) === 0 ? 'All dependencies satisfied' : 'Missing dependencies',
        ];
    }
}
