<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use stdClass;

/**
 * AddonManager
 *
 * Discovers and manages installable addons located in the dedicated addons/
 * directory. Addons are external/optional packages and are intentionally kept
 * separate from core modules.
 */
class AddonManager
{
    protected static ?Collection $addonsCache = null;

    /**
     * @return Collection<int, stdClass>
     */
    public static function all(): Collection
    {
        if (self::$addonsCache !== null) {
            return self::$addonsCache;
        }

        $addonsDir = base_path((string) config('catmin.addons.path', 'addons'));
        $addons = collect();

        if (!File::exists($addonsDir)) {
            self::$addonsCache = $addons;
            return self::$addonsCache;
        }

        foreach (File::directories($addonsDir) as $directory) {
            $addon = self::loadAddon($directory);
            if ($addon !== null) {
                $addons->push($addon);
            }
        }

        self::$addonsCache = $addons->sortBy('name')->values();

        return self::$addonsCache;
    }

    /**
     * @return Collection<int, stdClass>
     */
    public static function enabled(): Collection
    {
        return self::all()
            ->filter(fn ($addon) => (bool) ($addon->enabled ?? false))
            ->filter(function ($addon) {
                $slug = Str::lower((string) ($addon->slug ?? ''));

                foreach (self::dependenciesFor($slug) as $dependency) {
                    if (!ModuleManager::exists($dependency) || !ModuleManager::isDeclaredEnabled($dependency)) {
                        return false;
                    }
                }

                return true;
            })
            ->values();
    }

    public static function find(string $slug): ?stdClass
    {
        $normalized = Str::lower($slug);

        return self::all()->first(function ($addon) use ($normalized) {
            return Str::lower((string) ($addon->slug ?? '')) === $normalized;
        });
    }

    public static function exists(string $slug): bool
    {
        return self::find($slug) !== null;
    }

    public static function enable(string $slug): bool
    {
        return self::setEnabled($slug, true);
    }

    public static function disable(string $slug): bool
    {
        return self::setEnabled($slug, false);
    }

    /**
     * @return array<int, string>
     */
    public static function dependenciesFor(string $slug): array
    {
        $addon = self::find($slug);

        if ($addon === null) {
            return [];
        }

        return collect((array) ($addon->required_modules ?? $addon->depends_modules ?? []))
            ->map(fn ($dependency) => Str::lower((string) $dependency))
            ->filter(fn ($dependency) => $dependency !== '')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public static function addonDependenciesFor(string $slug): array
    {
        $addon = self::find($slug);

        if ($addon === null) {
            return [];
        }

        return collect((array) ($addon->dependencies ?? []))
            ->map(fn ($dependency) => Str::lower((string) $dependency))
            ->filter(fn ($dependency) => $dependency !== '')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array{allowed: bool, message: string, missing: array<int, string>}
     */
    public static function canEnable(string $slug): array
    {
        $addon = self::find($slug);

        if ($addon === null) {
            return [
                'allowed' => false,
                'message' => "Addon '{$slug}' introuvable.",
                'missing' => [],
            ];
        }

        $compatibility = app(AddonCompatibilityService::class)->evaluate(AddonManifestService::normalize((array) $addon));

        if (($compatibility['compatible'] ?? false) !== true) {
            return [
                'allowed' => false,
                'message' => "Impossible d'activer {$addon->name}: " . implode(' ', (array) ($compatibility['blockers'] ?? ['addon incompatible.'])),
                'missing' => (array) ($compatibility['blockers'] ?? []),
            ];
        }

        return [
            'allowed' => true,
            'message' => "Addon {$addon->name} activable.",
            'missing' => [],
            'warnings' => (array) ($compatibility['warnings'] ?? []),
        ];
    }

    public static function getRoutesPath(string $slug): ?string
    {
        $addon = self::find($slug);
        if ($addon === null) {
            return null;
        }

        $path = $addon->path . '/routes.php';

        return File::exists($path) ? $path : null;
    }

    public static function getHooksPath(string $slug): ?string
    {
        $addon = self::find($slug);
        if ($addon === null) {
            return null;
        }

        $path = $addon->path . '/hooks.php';

        return File::exists($path) ? $path : null;
    }

    /**
     * Detect missing required addon structure items.
     *
     * @return array<int, string>
     */
    public static function missingStructure(stdClass $addon): array
    {
        $manifest = AddonManifestService::normalize((array) $addon);
        $required = ['addon.json', 'Controllers', 'Services'];

        if ((bool) ($manifest['has_routes'] ?? false)) {
            $required[] = 'routes.php';
        }

        if ((bool) ($manifest['has_views'] ?? false)) {
            $required[] = 'Views';
        }

        if ((bool) ($manifest['has_migrations'] ?? false)) {
            $required[] = 'Migrations';
        }

        if ((bool) ($manifest['has_assets'] ?? false)) {
            $required[] = 'Assets';
        }

        $missing = [];

        foreach ($required as $item) {
            $target = $addon->path . '/' . $item;
            if (!File::exists($target)) {
                $missing[] = $item;
            }
        }

        return $missing;
    }

    /**
     * @return array{total: int, enabled: int, disabled: int, addons: array<int, array<string, mixed>>}
     */
    public static function summary(): array
    {
        $all = self::all();
        $enabled = self::enabled();

        return [
            'total' => $all->count(),
            'enabled' => $enabled->count(),
            'disabled' => $all->count() - $enabled->count(),
            'addons' => $all->map(fn ($addon) => [
                'type' => 'addon',
                'slug' => $addon->slug,
                'name' => $addon->name,
                'version' => $addon->version ?? 'unknown',
                'enabled' => (bool) ($addon->enabled ?? false),
                'required_core' => (bool) ($addon->requires_core ?? true),
                'depends_modules' => (array) ($addon->depends_modules ?? []),
            ])->toArray(),
        ];
    }

    public static function clearCache(): void
    {
        self::$addonsCache = null;
    }

    /**
     * @return Collection<int, stdClass>
     */
    public static function reload(): Collection
    {
        self::clearCache();

        return self::all();
    }

    protected static function loadAddon(string $directory): ?stdClass
    {
        $configPath = $directory . '/addon.json';

        if (!File::exists($configPath)) {
            return null;
        }

        try {
            $decoded = json_decode(File::get($configPath), true);

            if (!is_array($decoded)) {
                return null;
            }

            $normalized = AddonManifestService::normalize($decoded);
            $config = (object) $normalized;

            if (!isset($config->slug) || trim((string) $config->slug) === '') {
                return null;
            }

            $config->type = 'addon';
            $config->directory = basename($directory);
            $config->path = $directory;
            $config->namespace = 'Addons\\' . Str::studly((string) $config->slug);
            $config->requires_core = in_array('core', (array) ($config->required_modules ?? []), true);
            $config->depends_modules = (array) ($config->required_modules ?? []);
            $config->permissions = (array) ($config->permissions_declared ?? []);

            return $config;
        } catch (\Throwable) {
            return null;
        }
    }

    protected static function setEnabled(string $slug, bool $enabled): bool
    {
        $addon = self::find($slug);
        if ($addon === null) {
            return false;
        }

        if ($enabled) {
            $validation = self::canEnable($slug);
            if (!$validation['allowed']) {
                return false;
            }
        }

        $configPath = $addon->path . '/addon.json';
        if (!File::exists($configPath)) {
            return false;
        }

        $config = json_decode(File::get($configPath), true);
        if (!is_array($config)) {
            return false;
        }

        $config['enabled'] = $enabled;
        File::put($configPath, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        self::clearCache();

        // Keep addon behavior aligned with modules: enabling must make it immediately usable.
        if ($enabled && (bool) ($addon->has_migrations ?? false)) {
            try {
                $result = AddonMigrationRunner::runForAddon($slug);

                if (str_contains(strtolower((string) ($result['output'] ?? '')), 'failed')) {
                    $config['enabled'] = false;
                    File::put($configPath, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                    self::clearCache();

                    return false;
                }
            } catch (\Throwable) {
                $config['enabled'] = false;
                File::put($configPath, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                self::clearCache();

                return false;
            }
        }

        return true;
    }
}
