<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

class ExtensionContractValidatorService
{
    /**
     * @return array<string, mixed>
     */
    public function validateArchitectureBalance(): array
    {
        $checks = [];

        $checks['app_to_addons'] = $this->scanForbiddenNamespaceReferences(
            base_path('app'),
            '/\b(use\s+Addons\\\\|Addons\\\\[A-Za-z0-9_\\\\]+::class)/',
            [
                base_path('app/Console/Commands/CatminAddonMakeCommand.php'),
                base_path('app/Services/AddonManager.php'),
            ]
        );

        $checks['modules_to_addons'] = $this->scanForbiddenNamespaceReferences(
            base_path('modules'),
            '/\b(use\s+Addons\\\\|Addons\\\\[A-Za-z0-9_\\\\]+::class)/'
        );

        $checks['module_depends_on_addon'] = $this->findModuleDependenciesOnAddons();
        $checks['addon_module_usage_declared'] = $this->findUndeclaredAddonModuleUsages();

        $errors = [];

        foreach ($checks['app_to_addons'] as $violation) {
            $errors[] = sprintf('[core->addons] %s:%d %s', $violation['file'], (int) $violation['line'], $violation['message']);
        }

        foreach ($checks['modules_to_addons'] as $violation) {
            $errors[] = sprintf('[modules->addons] %s:%d %s', $violation['file'], (int) $violation['line'], $violation['message']);
        }

        foreach ($checks['module_depends_on_addon'] as $violation) {
            $errors[] = sprintf('[module-manifest] %s', $violation['message']);
        }

        foreach ($checks['addon_module_usage_declared'] as $violation) {
            $errors[] = sprintf('[addon-manifest] %s:%d %s', $violation['file'], (int) $violation['line'], $violation['message']);
        }

        return [
            'ok' => $errors === [],
            'errors' => $errors,
            'summary' => sprintf(
                '%d violation(s) - core->addons=%d, modules->addons=%d, module_manifest=%d, addon_manifest=%d',
                count($errors),
                count($checks['app_to_addons']),
                count($checks['modules_to_addons']),
                count($checks['module_depends_on_addon']),
                count($checks['addon_module_usage_declared'])
            ),
            'checks' => [
                'core_to_addons' => count($checks['app_to_addons']) === 0,
                'modules_to_addons' => count($checks['modules_to_addons']) === 0,
                'modules_manifest_no_addon_dependencies' => count($checks['module_depends_on_addon']) === 0,
                'addons_manifest_matches_module_usage' => count($checks['addon_module_usage_declared']) === 0,
            ],
            'metrics' => [
                'core_to_addons_violations' => count($checks['app_to_addons']),
                'modules_to_addons_violations' => count($checks['modules_to_addons']),
                'module_manifest_violations' => count($checks['module_depends_on_addon']),
                'addon_manifest_violations' => count($checks['addon_module_usage_declared']),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function validateModule(string $slug): array
    {
        $module = ModuleManager::find($slug);

        if ($module === null) {
            return $this->notFoundReport('module', $slug);
        }

        $path = (string) ($module->path ?? '');
        $report = $this->baseReport('module', (string) ($module->slug ?? $slug), $path);

        $manifestPath = $path . '/module.json';
        $manifest = $this->decodeJsonFile($manifestPath);

        if ($manifest === null) {
            $report['errors'][] = 'module.json absent ou invalide.';
        } else {
            $this->assertRequiredManifestKeys($report, $manifest, ['name', 'slug', 'version', 'enabled'], 'module.json');

            if (($manifest['slug'] ?? '') !== ($module->slug ?? $slug)) {
                $report['errors'][] = 'Slug module.json incoherent avec le dossier/module charge.';
            }

            if (!VersioningService::isValid((string) ($manifest['version'] ?? ''))) {
                $report['errors'][] = 'Version module invalide (semver x.y.z attendu).';
            }
        }

        foreach (['module.json', 'routes.php', 'Controllers', 'Views', 'Services'] as $requiredPath) {
            if (!File::exists($path . '/' . $requiredPath)) {
                $report['errors'][] = 'Structure module incomplete: element manquant ' . $requiredPath . '.';
            }
        }

        if (!File::exists($path . '/Docs') && !File::exists($path . '/README.md')) {
            $report['warnings'][] = 'Documentation module absente (Docs/ ou README.md recommande).';
        }

        $this->assertRoutePermissionContract($report, $path . '/routes.php', 'module');
        $this->assertNoDbUsageInViews($report, $path . '/Views');
        $this->assertHooksContract($report, $path . '/hooks.php');

        $report['ok'] = $report['errors'] === [];
        $report['summary'] = $this->summary($report);

        return $report;
    }

    /**
     * @return array<string, mixed>
     */
    public function validateAddon(string $slug): array
    {
        $addon = AddonManager::find($slug);

        if ($addon === null) {
            return $this->notFoundReport('addon', $slug);
        }

        $path = (string) ($addon->path ?? '');
        $report = $this->baseReport('addon', (string) ($addon->slug ?? $slug), $path);

        $manifestPath = $path . '/addon.json';
        $rawManifest = $this->decodeJsonFile($manifestPath);
        $manifest = $rawManifest !== null ? AddonManifestService::normalize($rawManifest) : null;

        if ($manifest === null || !AddonManifestService::isManifestValid($manifest)) {
            $report['errors'][] = 'addon.json absent ou invalide.';
        } else {
            $this->assertRequiredManifestKeys($report, $manifest, ['name', 'slug', 'version'], 'addon.json');

            if ((bool) ($manifest['requires_core'] ?? true) && !in_array('core', (array) ($manifest['required_modules'] ?? []), true)) {
                $report['errors'][] = 'Addon require core mais required_modules ne contient pas core.';
            }

            $uiHooks = (array) ($manifest['ui_hooks'] ?? []);
            foreach ($uiHooks as $hook) {
                $hook = (string) $hook;
                if (!str_starts_with($hook, 'before:') && !str_starts_with($hook, 'after:')) {
                    $report['warnings'][] = 'UI hook hors contrat: ' . $hook . ' (before:/after: attendu).';
                }
            }

            $hasAdminRoutes = $this->hasAdminRoutes($path . '/routes.php');
            $permissionsDeclared = (array) ($manifest['permissions_declared'] ?? []);
            if ($hasAdminRoutes && $permissionsDeclared === []) {
                $report['errors'][] = 'Routes admin detectees sans permissions_declared dans addon.json.';
            }
        }

        foreach (['addon.json', 'Controllers', 'Views', 'Services'] as $requiredPath) {
            if (!File::exists($path . '/' . $requiredPath)) {
                $report['errors'][] = 'Structure addon incomplete: element manquant ' . $requiredPath . '.';
            }
        }

        if ($manifest !== null && (bool) ($manifest['has_routes'] ?? false) && !File::exists($path . '/routes.php')) {
            $report['errors'][] = 'Manifest declare has_routes=true mais routes.php est absent.';
        }

        if ($manifest !== null && (bool) ($manifest['has_migrations'] ?? false) && !File::exists($path . '/Migrations')) {
            $report['errors'][] = 'Manifest declare has_migrations=true mais Migrations/ est absent.';
        }

        if (!File::exists($path . '/Docs') && !File::exists($path . '/README.md')) {
            $report['warnings'][] = 'Documentation addon absente (Docs/ ou README.md recommande).';
        }

        $this->assertRoutePermissionContract($report, $path . '/routes.php', 'addon');
        $this->assertNoDbUsageInViews($report, $path . '/Views');
        $this->assertHooksContract($report, $path . '/hooks.php');
        $this->assertNoCoreCoupling($report, $path);

        $report['ok'] = $report['errors'] === [];
        $report['summary'] = $this->summary($report);

        return $report;
    }

    /**
     * @return array<string, mixed>
     */
    public function validateAll(): array
    {
        $modules = ModuleManager::all()->map(fn ($module) => $this->validateModule((string) $module->slug))->values()->all();
        $addons = AddonManager::all()->map(fn ($addon) => $this->validateAddon((string) $addon->slug))->values()->all();

        $moduleFailures = collect($modules)->where('ok', false)->count();
        $addonFailures = collect($addons)->where('ok', false)->count();

        return [
            'ok' => ($moduleFailures + $addonFailures) === 0,
            'modules' => $modules,
            'addons' => $addons,
            'summary' => [
                'modules_total' => count($modules),
                'modules_failed' => $moduleFailures,
                'addons_total' => count($addons),
                'addons_failed' => $addonFailures,
                'warnings' => collect($modules)->sum(fn (array $r) => count((array) ($r['warnings'] ?? [])))
                    + collect($addons)->sum(fn (array $r) => count((array) ($r['warnings'] ?? []))),
            ],
        ];
    }

    /**
     * @param array<string, mixed> $report
     */
    private function assertRoutePermissionContract(array &$report, string $routesPath, string $type): void
    {
        if (!File::exists($routesPath)) {
            return;
        }

        $content = (string) File::get($routesPath);
        if (!$this->hasAdminRoutes($routesPath)) {
            return;
        }

        if (!str_contains($content, 'catmin.permission:')) {
            $report['errors'][] = sprintf('%s routes admin sans middleware catmin.permission detectees.', ucfirst($type));
        }
    }

    private function hasAdminRoutes(string $routesPath): bool
    {
        if (!File::exists($routesPath)) {
            return false;
        }

        $content = (string) File::get($routesPath);

        return str_contains($content, "->name('admin.")
            || str_contains($content, '->name("admin.')
            || str_contains($content, '/admin/')
            || str_contains($content, "Route::prefix('admin")
            || str_contains($content, 'Route::prefix("admin');
    }

    /**
     * @param array<string, mixed> $report
     */
    private function assertNoDbUsageInViews(array &$report, string $viewsPath): void
    {
        if (!File::exists($viewsPath)) {
            return;
        }

        foreach (File::allFiles($viewsPath) as $file) {
            if (!str_ends_with((string) $file->getFilename(), '.blade.php')) {
                continue;
            }

            $content = (string) File::get($file->getPathname());
            if (preg_match('/\b(DB|Schema)::|->table\s*\(/', $content) === 1) {
                $report['errors'][] = 'Acces DB detecte dans une vue: ' . $this->relativePath($file->getPathname()) . '.';
            }
        }
    }

    /**
     * @param array<string, mixed> $report
     */
    private function assertHooksContract(array &$report, string $hooksPath): void
    {
        if (!File::exists($hooksPath)) {
            return;
        }

        $content = (string) File::get($hooksPath);
        $usesHookRegistry = str_contains($content, 'CatminHookRegistry::');
        $usesEventBus = str_contains($content, 'CatminEventBus::');

        if (!$usesHookRegistry && !$usesEventBus) {
            $report['warnings'][] = 'hooks.php present mais aucun usage explicite CatminHookRegistry/CatminEventBus.';
        }
    }

    /**
     * @param array<string, mixed> $report
     */
    private function assertNoCoreCoupling(array &$report, string $addonPath): void
    {
        if (!File::exists($addonPath)) {
            return;
        }

        foreach (File::allFiles($addonPath) as $file) {
            $name = (string) $file->getFilename();
            if (!str_ends_with($name, '.php')) {
                continue;
            }

            $content = (string) File::get($file->getPathname());
            if (preg_match('/base_path\(["\']core\/|\/core\//i', $content) === 1) {
                $report['errors'][] = 'Couplage direct au core detecte: ' . $this->relativePath($file->getPathname()) . '.';
            }
        }
    }

    /**
     * @param array<string, mixed> $report
     * @param array<string, mixed> $manifest
     * @param array<int, string> $requiredKeys
     */
    private function assertRequiredManifestKeys(array &$report, array $manifest, array $requiredKeys, string $file): void
    {
        foreach ($requiredKeys as $key) {
            $value = $manifest[$key] ?? null;
            if ($value === null || (is_string($value) && trim($value) === '')) {
                $report['errors'][] = $file . ' champ obligatoire manquant: ' . $key . '.';
            }
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodeJsonFile(string $path): ?array
    {
        if (!File::exists($path)) {
            return null;
        }

        $decoded = json_decode((string) File::get($path), true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function baseReport(string $type, string $slug, string $path): array
    {
        return [
            'ok' => false,
            'type' => $type,
            'slug' => $slug,
            'path' => $this->relativePath($path),
            'errors' => [],
            'warnings' => [],
            'summary' => '',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function notFoundReport(string $type, string $slug): array
    {
        $report = $this->baseReport($type, $slug, '');
        $report['errors'][] = ucfirst($type) . ' introuvable: ' . $slug . '.';
        $report['summary'] = $this->summary($report);

        return $report;
    }

    /**
     * @param array<string, mixed> $report
     */
    private function summary(array $report): string
    {
        return sprintf(
            '%d erreur(s), %d warning(s)',
            count((array) ($report['errors'] ?? [])),
            count((array) ($report['warnings'] ?? []))
        );
    }

    private function relativePath(string $path): string
    {
        $base = rtrim(base_path(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        return str_starts_with($path, $base) ? substr($path, strlen($base)) : $path;
    }

    /**
     * @param array<int, string> $excludedPaths
     * @return array<int, array{file: string, line: int, message: string}>
     */
    private function scanForbiddenNamespaceReferences(string $rootPath, string $pattern, array $excludedPaths = []): array
    {
        if (!File::exists($rootPath)) {
            return [];
        }

        $violations = [];
        $excluded = array_map(static fn (string $path): string => str_replace('\\', '/', $path), $excludedPaths);

        foreach (File::allFiles($rootPath) as $file) {
            $path = (string) $file->getPathname();
            if (!str_ends_with($path, '.php')) {
                continue;
            }

            $normalizedPath = str_replace('\\', '/', $path);
            if (in_array($normalizedPath, $excluded, true)) {
                continue;
            }

            $lines = preg_split('/\R/', (string) File::get($path)) ?: [];
            foreach ($lines as $index => $line) {
                if (preg_match($pattern, (string) $line) === 1) {
                    $violations[] = [
                        'file' => $this->relativePath($path),
                        'line' => $index + 1,
                        'message' => 'Reference Addons\\ detected in a forbidden layer.',
                    ];
                }
            }
        }

        return $violations;
    }

    /**
     * @return array<int, array{module: string, dependency: string, message: string}>
     */
    private function findModuleDependenciesOnAddons(): array
    {
        $violations = [];

        foreach (ModuleManager::all() as $module) {
            $slug = strtolower((string) ($module->slug ?? ''));
            $dependencies = collect((array) ($module->depends ?? $module->dependencies ?? []))
                ->map(fn ($dependency) => strtolower((string) $dependency))
                ->filter(fn ($dependency) => $dependency !== '')
                ->values()
                ->all();

            foreach ($dependencies as $dependency) {
                if (AddonManager::exists($dependency)) {
                    $violations[] = [
                        'module' => $slug,
                        'dependency' => $dependency,
                        'message' => "Module '{$slug}' depends on addon '{$dependency}' (forbidden: modules may not depend on addons).",
                    ];
                }
            }
        }

        return $violations;
    }

    /**
     * @return array<int, array{file: string, line: int, message: string}>
     */
    private function findUndeclaredAddonModuleUsages(): array
    {
        $violations = [];

        foreach (AddonManager::all() as $addon) {
            $addonPath = (string) ($addon->path ?? '');
            if ($addonPath === '' || !File::exists($addonPath)) {
                continue;
            }

            $declaredModules = collect(array_merge(
                (array) ($addon->required_modules ?? []),
                (array) ($addon->depends_modules ?? [])
            ))
                ->map(fn ($row) => strtolower((string) $row))
                ->filter(fn ($row) => $row !== '')
                ->unique()
                ->values()
                ->all();

            foreach (File::allFiles($addonPath) as $file) {
                $path = (string) $file->getPathname();
                if (!str_ends_with($path, '.php')) {
                    continue;
                }

                $lines = preg_split('/\R/', (string) File::get($path)) ?: [];
                foreach ($lines as $index => $line) {
                    if (preg_match_all('/Modules\\\\([A-Za-z0-9_]+)\\\\/', (string) $line, $matches) !== 1) {
                        continue;
                    }

                    foreach ((array) ($matches[1] ?? []) as $segment) {
                        $candidate = strtolower((string) \Illuminate\Support\Str::kebab((string) $segment));
                        if (!ModuleManager::exists($candidate)) {
                            continue;
                        }

                        if (!in_array($candidate, $declaredModules, true)) {
                            $violations[] = [
                                'file' => $this->relativePath($path),
                                'line' => $index + 1,
                                'message' => "Addon uses module '{$candidate}' but it is missing from required_modules/depends_modules.",
                            ];
                        }
                    }
                }
            }
        }

        return $violations;
    }
}
