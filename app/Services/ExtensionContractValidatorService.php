<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

class ExtensionContractValidatorService
{
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
}
