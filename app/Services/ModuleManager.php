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
     * Modules that cannot be disabled in V1.
     *
     * @var array<int, string>
     */
    protected static array $protectedModules = ['core'];

    /**
     * Minimal fallback dependencies for V1 safety rules.
     *
     * @var array<string, array<int, string>>
     */
    protected static array $fallbackDependencies = [
        'pages' => ['core', 'seo'],
        'articles' => ['core', 'media', 'seo'],
        'menus' => ['core', 'pages'],
        'blocks' => ['core', 'pages'],
    ];

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
        $declaredEnabled = self::all()->filter(fn ($module) => (bool) ($module->enabled ?? false));
        $declaredEnabledSlugs = $declaredEnabled
            ->pluck('slug')
            ->map(fn ($slug) => Str::lower((string) $slug));

        return $declaredEnabled->filter(function ($module) use ($declaredEnabledSlugs) {
            $slug = Str::lower((string) ($module->slug ?? ''));

            if (self::isProtectedModule($slug)) {
                return true;
            }

            foreach (self::dependenciesFor($slug) as $dependency) {
                if (!$declaredEnabledSlugs->contains($dependency)) {
                    return false;
                }
            }

            return true;
        })->values();
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
        $normalizedSlug = Str::lower($slug);

        return self::enabled()->contains(fn ($module) => Str::lower((string) ($module->slug ?? '')) === $normalizedSlug);
    }

    /**
     * Check if a module is declared enabled in module.json.
     */
    public static function isDeclaredEnabled(string $slug): bool
    {
        $module = self::find($slug);

        return $module !== null && (bool) ($module->enabled ?? false);
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

        $validation = $enabled ? self::canEnable($slug) : self::canDisable($slug);
        if (!$validation['allowed']) {
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
     * Validate if a module can be enabled.
     *
     * @return array{allowed: bool, message: string, missing: array<int, string>}
     */
    public static function canEnable(string $slug): array
    {
        $normalizedSlug = Str::lower($slug);
        $module = self::find($normalizedSlug);

        if ($module === null) {
            return [
                'allowed' => false,
                'message' => "Module '{$slug}' introuvable.",
                'missing' => [],
            ];
        }

        $missing = [];
        foreach (self::dependenciesFor($normalizedSlug) as $dependency) {
            if (!self::exists($dependency) || !self::isDeclaredEnabled($dependency)) {
                $missing[] = $dependency;
            }
        }

        if ($missing !== []) {
            return [
                'allowed' => false,
                'message' => "Impossible d'activer {$module->name}: dépendances manquantes ou désactivées (" . implode(', ', $missing) . ').',
                'missing' => $missing,
            ];
        }

        return [
            'allowed' => true,
            'message' => "Module {$module->name} activable.",
            'missing' => [],
        ];
    }

    /**
     * Validate if a module can be disabled.
     *
     * @return array{allowed: bool, message: string, dependents: array<int, string>}
     */
    public static function canDisable(string $slug): array
    {
        $normalizedSlug = Str::lower($slug);
        $module = self::find($normalizedSlug);

        if ($module === null) {
            return [
                'allowed' => false,
                'message' => "Module '{$slug}' introuvable.",
                'dependents' => [],
            ];
        }

        if (self::isProtectedModule($normalizedSlug)) {
            return [
                'allowed' => false,
                'message' => "Impossible de désactiver {$module->name}: module système protégé.",
                'dependents' => [],
            ];
        }

        $dependents = self::all()
            ->filter(fn ($m) => (bool) ($m->enabled ?? false))
            ->filter(function ($m) use ($normalizedSlug) {
                $dependentSlug = Str::lower((string) ($m->slug ?? ''));

                if ($dependentSlug === $normalizedSlug) {
                    return false;
                }

                return in_array($normalizedSlug, self::dependenciesFor($dependentSlug), true);
            })
            ->pluck('name')
            ->map(fn ($name) => (string) $name)
            ->values()
            ->all();

        if ($dependents !== []) {
            return [
                'allowed' => false,
                'message' => "Impossible de désactiver {$module->name}: requis par " . implode(', ', $dependents) . '.',
                'dependents' => $dependents,
            ];
        }

        return [
            'allowed' => true,
            'message' => "Module {$module->name} désactivable.",
            'dependents' => [],
        ];
    }

    /**
     * Return global dependency/security issues for current state.
     *
     * @return array<int, array{level: string, code: string, message: string}>
     */
    public static function stateIssues(): array
    {
        $issues = [];

        if (!self::isDeclaredEnabled('core')) {
            $issues[] = [
                'level' => 'critical',
                'code' => 'core-disabled',
                'message' => 'Le module Core est désactivé dans la configuration. Cet état est invalide en V1.',
            ];
        }

        foreach (self::all()->filter(fn ($m) => (bool) ($m->enabled ?? false)) as $module) {
            $slug = Str::lower((string) ($module->slug ?? ''));

            foreach (self::dependenciesFor($slug) as $dependency) {
                if (!self::exists($dependency) || !self::isDeclaredEnabled($dependency)) {
                    $issues[] = [
                        'level' => 'warning',
                        'code' => 'dependency-missing',
                        'message' => "Le module {$module->name} dépend de '{$dependency}' qui est absent ou désactivé.",
                    ];
                }
            }
        }

        return $issues;
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

    /**
     * @return array<int, string>
     */
    protected static function dependenciesFor(string $slug): array
    {
        $normalizedSlug = Str::lower($slug);
        $module = self::find($normalizedSlug);
        $declared = collect((array) ($module->depends ?? []))
            ->map(fn ($dependency) => Str::lower((string) $dependency));
        $fallback = collect(self::$fallbackDependencies[$normalizedSlug] ?? [])
            ->map(fn ($dependency) => Str::lower((string) $dependency));

        return $declared
            ->merge($fallback)
            ->filter(fn ($dependency) => $dependency !== '')
            ->unique()
            ->values()
            ->all();
    }

    protected static function isProtectedModule(string $slug): bool
    {
        return in_array(Str::lower($slug), self::$protectedModules, true);
    }
}
