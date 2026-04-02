<?php

namespace App\Services;

use App\Models\Role;
use Illuminate\Routing\Route as LaravelRoute;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class RbacAuditService
{
    /**
     * @return array<string, mixed>
     */
    public static function generate(): array
    {
        $adminPath = trim((string) config('catmin.admin.path', 'admin'), '/');

        $rows = collect(Route::getRoutes())
            ->map(fn (LaravelRoute $route) => self::inspectRoute($route, $adminPath))
            ->filter(fn (?array $row) => $row !== null)
            ->values();

        $excluded = $rows->where('excluded', true)->values();
        $adminRoutes = $rows->where('excluded', false)->values();
        $sensitive = $adminRoutes->where('sensitive', true)->values();

        $protected = $sensitive->where('has_permission', true)->values();
        $unprotected = $sensitive->where('has_permission', false)->values();

        $inconsistent = $sensitive
            ->filter(function (array $route): bool {
                if (!(bool) ($route['has_permission'] ?? false)) {
                    return false;
                }

                $expected = (string) ($route['expected_permission'] ?? '');
                $actual = (string) ($route['permission'] ?? '');

                return $expected !== '' && $actual !== '' && $expected !== $actual;
            })
            ->values();

        $usedPermissions = $protected
            ->pluck('permission')
            ->filter()
            ->unique()
            ->sort()
            ->values();

        $declaredPermissions = self::declaredPermissions();

        $declaredUnused = $declaredPermissions
            ->diff($usedPermissions)
            ->values();

        $summary = [
            'admin_routes_total' => $adminRoutes->count(),
            'admin_routes_excluded' => $excluded->count(),
            'sensitive_routes_total' => $sensitive->count(),
            'sensitive_routes_protected' => $protected->count(),
            'sensitive_routes_unprotected' => $unprotected->count(),
            'sensitive_coverage_percent' => $sensitive->count() > 0
                ? round(($protected->count() / $sensitive->count()) * 100, 1)
                : 100.0,
            'inconsistent_routes' => $inconsistent->count(),
            'permissions_used' => $usedPermissions->count(),
            'permissions_declared_unused' => $declaredUnused->count(),
        ];

        return [
            'generated_at' => Carbon::now()->toIso8601String(),
            'summary' => $summary,
            'sensitive_protected' => $protected->all(),
            'sensitive_unprotected' => $unprotected->all(),
            'inconsistent_permissions' => $inconsistent->all(),
            'permissions_used' => $usedPermissions->all(),
            'permissions_declared_unused' => $declaredUnused->all(),
            'excluded_routes' => $excluded->all(),
        ];
    }

    /**
     * @param array<string, mixed> $report
     * @return array{json: string, markdown: string}
     */
    public static function writeReport(array $report): array
    {
        $timestamp = Carbon::now()->format('Ymd-His');
        $baseName = 'rbac-audit-' . $timestamp;
        $dir = 'reports';

        Storage::disk('local')->makeDirectory($dir);

        $jsonPath = $dir . '/' . $baseName . '.json';
        $mdPath = $dir . '/' . $baseName . '.md';

        Storage::disk('local')->put($jsonPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n");
        Storage::disk('local')->put($mdPath, self::toMarkdown($report));

        return [
            'json' => storage_path('app/' . $jsonPath),
            'markdown' => storage_path('app/' . $mdPath),
        ];
    }

    /**
     * @param array<string, mixed> $report
     */
    protected static function toMarkdown(array $report): string
    {
        $summary = (array) ($report['summary'] ?? []);
        $lines = [
            '# CATMIN RBAC Audit Report',
            '',
            '- Generated at: ' . (string) ($report['generated_at'] ?? ''),
            '- Sensitive coverage: ' . (string) ($summary['sensitive_coverage_percent'] ?? 0) . '%',
            '- Sensitive protected: ' . (string) ($summary['sensitive_routes_protected'] ?? 0),
            '- Sensitive unprotected: ' . (string) ($summary['sensitive_routes_unprotected'] ?? 0),
            '- Inconsistent permissions: ' . (string) ($summary['inconsistent_routes'] ?? 0),
            '',
            '## Sensitive Unprotected Routes',
        ];

        foreach ((array) ($report['sensitive_unprotected'] ?? []) as $route) {
            $lines[] = '- ' . (string) ($route['name'] ?? 'unnamed') . ' [' . (string) ($route['methods'] ?? '') . '] /' . (string) ($route['uri'] ?? '');
        }

        if (empty((array) ($report['sensitive_unprotected'] ?? []))) {
            $lines[] = '- none';
        }

        $lines[] = '';
        $lines[] = '## Inconsistent Permission Mapping';

        foreach ((array) ($report['inconsistent_permissions'] ?? []) as $route) {
            $lines[] = '- ' . (string) ($route['name'] ?? 'unnamed') . ': expected `' . (string) ($route['expected_permission'] ?? '') . '`, got `' . (string) ($route['permission'] ?? '') . '`';
        }

        if (empty((array) ($report['inconsistent_permissions'] ?? []))) {
            $lines[] = '- none';
        }

        $lines[] = '';

        return implode("\n", $lines) . "\n";
    }

    /**
     * @return array<string, mixed>|null
     */
    protected static function inspectRoute(LaravelRoute $route, string $adminPath): ?array
    {
        $uri = trim($route->uri(), '/');

        if (!str_starts_with($uri, $adminPath)) {
            return null;
        }

        $name = (string) ($route->getName() ?? 'unnamed');
        $methods = array_values(array_diff($route->methods(), ['HEAD']));
        $middleware = $route->middleware();

        $permission = null;
        foreach ($middleware as $item) {
            if (str_starts_with($item, 'catmin.permission:')) {
                $permission = substr($item, strlen('catmin.permission:'));
                break;
            }
        }

        $excluded = self::isExcludedRoute($name, $uri);
        $expected = self::expectedPermission($name, $methods);
        $sensitive = !$excluded && self::isSensitiveRoute($name, $methods);

        return [
            'name' => $name,
            'uri' => $uri,
            'methods' => implode('|', $methods),
            'middleware' => $middleware,
            'has_permission' => $permission !== null,
            'permission' => $permission,
            'expected_permission' => $expected,
            'sensitive' => $sensitive,
            'excluded' => $excluded,
        ];
    }

    protected static function isExcludedRoute(string $name, string $uri): bool
    {
        if (str_ends_with($name, '.login') || str_contains($name, '.login.') || str_ends_with($name, '.logout')) {
            return true;
        }

        if (str_contains($name, '.password.') || str_contains($uri, '/forgot-password') || str_contains($uri, '/reset-password')) {
            return true;
        }

        if (str_contains($name, '.2fa.') || str_contains($uri, '/2fa/')) {
            return true;
        }

        if (str_contains($name, '.error.') || str_contains($uri, '/errors/')) {
            return true;
        }

        return false;
    }

    /**
     * @param array<int, string> $methods
     */
    protected static function isSensitiveRoute(string $name, array $methods): bool
    {
        if ($name === 'admin.index' || $name === 'admin.access') {
            return false;
        }

        if (in_array('POST', $methods, true) || in_array('PUT', $methods, true) || in_array('PATCH', $methods, true) || in_array('DELETE', $methods, true)) {
            return true;
        }

        return str_starts_with($name, 'admin.users.')
            || str_starts_with($name, 'admin.roles.')
            || str_starts_with($name, 'admin.settings.')
            || str_starts_with($name, 'admin.modules.')
            || str_starts_with($name, 'admin.addons.')
            || str_starts_with($name, 'admin.queue.')
            || str_starts_with($name, 'admin.cron.')
            || str_starts_with($name, 'admin.webhooks.')
            || str_starts_with($name, 'admin.mailer.')
            || str_starts_with($name, 'admin.logger.')
            || str_starts_with($name, 'admin.cache.')
            || str_starts_with($name, 'admin.docs.')
            || str_starts_with($name, 'admin.shop.');
    }

    /**
     * @param array<int, string> $methods
     */
    protected static function expectedPermission(string $name, array $methods): string
    {
        if ($name === 'admin.users.index' || $name === 'admin.users.manage') {
            return 'module.users.list';
        }

        if ($name === 'admin.roles.index' || $name === 'admin.roles.manage') {
            return 'module.users.config';
        }

        if ($name === 'admin.settings.index' || $name === 'admin.settings.manage') {
            return 'module.settings.list';
        }

        if (str_starts_with($name, 'admin.modules.')) {
            return str_ends_with($name, '.index') ? 'module.core.list' : 'module.core.config';
        }

        if (str_starts_with($name, 'admin.addons.marketplace.')) {
            return 'module.core.config';
        }

        $compatibilityOverrides = [
            // Historical compatibility: roles are governed by users.config.
            'admin.roles.create' => 'module.users.config',
            'admin.roles.store' => 'module.users.config',
            'admin.roles.edit' => 'module.users.config',
            'admin.roles.update' => 'module.users.config',
            'admin.roles.preview.start' => 'module.users.config',
            'admin.roles.preview.stop' => 'module.users.config',
            'admin.roles.destroy' => 'module.users.config',

            // Existing module semantics where config/edit actions are intentionally shared.
            'admin.settings.update' => 'module.settings.config',
            'admin.pages.toggle_status' => 'module.pages.edit',
            'admin.pages.destroy' => 'module.pages.trash',
            'admin.pages.restore' => 'module.pages.trash',
            'admin.pages.force_delete' => 'module.pages.trash',
            'admin.pages.trash.empty' => 'module.pages.trash',
            'admin.articles.toggle_status' => 'module.articles.edit',
            'admin.articles.destroy' => 'module.articles.trash',
            'admin.articles.restore' => 'module.articles.trash',
            'admin.articles.force_delete' => 'module.articles.trash',
            'admin.articles.trash.empty' => 'module.articles.trash',
            'admin.articles.categories.index' => 'module.articles.config',
            'admin.articles.categories.store' => 'module.articles.config',
            'admin.articles.categories.edit' => 'module.articles.config',
            'admin.articles.categories.update' => 'module.articles.config',
            'admin.articles.categories.destroy' => 'module.articles.config',
            'admin.articles.tags.index' => 'module.articles.config',
            'admin.articles.tags.store' => 'module.articles.config',
            'admin.articles.tags.edit' => 'module.articles.config',
            'admin.articles.tags.update' => 'module.articles.config',
            'admin.articles.tags.destroy' => 'module.articles.config',
            'admin.media.destroy' => 'module.media.trash',
            'admin.media.restore' => 'module.media.trash',
            'admin.media.force_delete' => 'module.media.trash',
            'admin.media.trash.empty' => 'module.media.trash',
            'admin.blocks.toggle_status' => 'module.blocks.edit',
            'admin.menus.toggle_status' => 'module.menus.edit',
            'admin.menus.items.store' => 'module.menus.edit',
            'admin.menus.items.toggle_status' => 'module.menus.edit',
            'admin.queue.failed.delete' => 'module.queue.config',
            'admin.shop.toggle_status' => 'module.shop.edit',
            'admin.shop.invoices.settings' => 'module.shop.config',
            'admin.shop.invoices.settings.update' => 'module.shop.config',
            'admin.mailer.config.update' => 'module.mailer.config',

            // Session management is owned by core permissions.
            'admin.sessions.index' => 'module.core.list',
            'admin.sessions.revoke' => 'module.core.config',
            'admin.sessions.revoke-others' => 'module.core.config',
        ];

        if (isset($compatibilityOverrides[$name])) {
            return $compatibilityOverrides[$name];
        }

        $trimmed = str_starts_with($name, 'admin.') ? substr($name, 6) : $name;
        $parts = explode('.', $trimmed);

        if (count($parts) < 2) {
            return '';
        }

        $module = strtolower((string) $parts[0]);
        $action = strtolower((string) end($parts));

        $map = [
            'index' => 'list',
            'manage' => 'list',
            'show' => 'list',
            'create' => 'create',
            'store' => 'create',
            'edit' => 'edit',
            'update' => 'edit',
            'destroy' => 'delete',
            'delete' => 'delete',
            'toggle_active' => 'config',
            'enable' => 'config',
            'disable' => 'config',
            'config' => 'config',
            'rebuild' => 'config',
            'migrate' => 'config',
            'retry' => 'config',
            'run' => 'config',
            'send' => 'config',
        ];

        $normalizedAction = $map[$action] ?? (in_array('GET', $methods, true) ? 'list' : 'config');

        return RbacPermissionService::modulePermission($module, $normalizedAction);
    }

    /**
     * @return \Illuminate\Support\Collection<int, string>
     */
    protected static function declaredPermissions()
    {
        try {
            if (!Schema::hasTable('roles')) {
                return collect();
            }

            return Role::query()
                ->pluck('permissions')
                ->flatMap(fn ($permissions) => (array) $permissions)
                ->map(fn ($permission) => (string) $permission)
                ->filter(fn (string $permission) => $permission !== '' && $permission !== '*')
                ->unique()
                ->sort()
                ->values();
        } catch (\Throwable) {
            return collect();
        }
    }
}
