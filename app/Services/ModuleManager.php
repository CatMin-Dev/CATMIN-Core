<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use stdClass;

/**
 * ModuleManager
 *
 * Manages module discovery, loading, and configuration within CATMIN.
 * Provides a simple but extensible interface for module management.
 *
 * Usage:
 *   $modules = ModuleManager::all();
 *   $enabled = ModuleManager::enabled();
 *   $core = ModuleManager::find('core');
 *   $isEnabled = ModuleManager::isEnabled('blog');
 */
class ModuleManager
{
    /**
     * Modules base directory
     */
    protected static string $modulesPath = 'modules';

    /**
     * Cached modules
     *
     * @var Collection|null
     */
    protected static ?Collection $modulesCache = null;

    /**
     * Get all modules (enabled and disabled)
     *
     * @return Collection<int, stdClass>
     */
    public static function all(): Collection
    {
        if (self::$modulesCache !== null) {
            return self::$modulesCache;
        }

        $modules = collect();
        $modulesDir = base_path(self::$modulesPath);

        if (!File::exists($modulesDir)) {
            return $modules;
        }

        $directories = File::directories($modulesDir);

        foreach ($directories as $directory) {
            $module = self::loadModule($directory);
            if ($module) {
                $modules->push($module);
            }
        }

        self::$modulesCache = $modules->sortBy('name');

        return self::$modulesCache;
    }

    /**
     * Get only enabled modules
     *
     * @return Collection<int, stdClass>
     */
    public static function enabled(): Collection
    {
        return self::all()->filter(fn ($module) => $module->enabled);
    }

    /**
     * Get only disabled modules
     *
     * @return Collection<int, stdClass>
     */
    public static function disabled(): Collection
    {
        return self::all()->filter(fn ($module) => !$module->enabled);
    }

    /**
     * Find a module by slug
     *
     * @param string $slug
     * @return stdClass|null
     */
    public static function find(string $slug): ?stdClass
    {
        return self::all()->firstWhere('slug', $slug);
    }

    /**
     * Check if a module exists
     *
     * @param string $slug
     * @return bool
     */
    public static function exists(string $slug): bool
    {
        return self::find($slug) !== null;
    }

    /**
     * Check if a module is enabled
     *
     * @param string $slug
     * @return bool
     */
    public static function isEnabled(string $slug): bool
    {
        $module = self::find($slug);
        return $module && $module->enabled;
    }

    /**
     * Enable a module
     *
     * @param string $slug
     * @return bool
     */
    public static function enable(string $slug): bool
    {
        return self::setEnabled($slug, true);
    }

    /**
     * Disable a module
     *
     * @param string $slug
     * @return bool
     */
    public static function disable(string $slug): bool
    {
        return self::setEnabled($slug, false);
    }

    /**
     * Set module enabled/disabled state
     *
     * @param string $slug
     * @param bool $enabled
     * @return bool
     */
    protected static function setEnabled(string $slug, bool $enabled): bool
    {
        $module = self::find($slug);
        if (!$module) {
            return false;
        }

        $configPath = base_path(self::$modulesPath . '/' . $module->directory . '/module.json');

        if (!File::exists($configPath)) {
            return false;
        }

        $config = json_decode(File::get($configPath), true);
        $config['enabled'] = $enabled;

        File::put($configPath, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        // Clear cache
        self::$modulesCache = null;

        return true;
    }

    /**
     * Get module routes file path
     *
     * @param string $slug
     * @return string|null
     */
    public static function getRoutesPath(string $slug): ?string
    {
        $module = self::find($slug);
        if (!$module) {
            return null;
        }

        $path = base_path(self::$modulesPath . '/' . $module->directory . '/routes.php');
        return File::exists($path) ? $path : null;
    }

    /**
     * Load module configuration from module.json
     *
     * @param string $directory
     * @return stdClass|null
     */
    protected static function loadModule(string $directory): ?stdClass
    {
        $configPath = $directory . '/module.json';

        if (!File::exists($configPath)) {
            return null;
        }

        try {
            $config = json_decode(File::get($configPath), false);

            if (!$config instanceof stdClass) {
                return null;
            }

            // Add additional computed properties
            $config->directory = basename($directory);
            $config->path = $directory;
            $config->namespace = 'Modules\\' . Str::studly($config->slug ?? basename($directory));

            return $config;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Clear module cache
     *
     * @return void
     */
    public static function clearCache(): void
    {
        self::$modulesCache = null;
    }

    /**
     * Reload modules from disk
     *
     * @return Collection<int, stdClass>
     */
    public static function reload(): Collection
    {
        self::clearCache();
        return self::all();
    }

    /**
     * Get modules count
     *
     * @return int
     */
    public static function count(): int
    {
        return self::all()->count();
    }

    /**
     * Get enabled modules count
     *
     * @return int
     */
    public static function enabledCount(): int
    {
        return self::enabled()->count();
    }

    /**
     * Get summary of all modules
     *
     * @return array
     */
    public static function summary(): array
    {
        return [
            'total' => self::count(),
            'enabled' => self::enabledCount(),
            'disabled' => self::count() - self::enabledCount(),
            'modules' => self::all()->map(fn ($m) => [
                'slug' => $m->slug,
                'name' => $m->name,
                'version' => $m->version ?? 'unknown',
                'enabled' => $m->enabled,
                'depends' => $m->depends ?? [],
            ])->toArray(),
        ];
    }
}
