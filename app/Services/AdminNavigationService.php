<?php

namespace App\Services;

use Illuminate\Support\Facades\Route;

class AdminNavigationService
{
    /**
     * Build sidebar navigation sections for the admin UI.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function sections(?string $currentPage = null): array
    {
        $configuredSections = config('catmin.navigation.sections', []);

        return collect($configuredSections)
            ->map(fn (array $section) => self::buildSection($section, $currentPage))
            ->filter(fn (array $section) => !empty($section['items']))
            ->values()
            ->all();
    }

    /**
     * @param array<string, mixed> $section
     * @return array<string, mixed>
     */
    protected static function buildSection(array $section, ?string $currentPage): array
    {
        if (($section['source'] ?? null) === 'enabled_modules') {
            $items = self::enabledModuleItems();
        } else {
            $items = collect($section['items'] ?? [])
                ->filter(fn (array $item) => self::itemIsVisible($item))
                ->map(fn (array $item) => self::normalizeItem($item, $currentPage))
                ->filter(fn (array $item) => !empty($item['url']))
                ->values()
                ->all();
        }

        return [
            'title' => $section['title'] ?? 'Navigation',
            'items' => $items,
        ];
    }

    /**
     * @param array<string, mixed> $item
     */
    protected static function itemIsVisible(array $item): bool
    {
        if (!empty($item['feature']) && !config('catmin.features.' . $item['feature'], false)) {
            return false;
        }

        $requiredModule = self::resolveRequiredModule($item);

        if ($requiredModule !== null && !ModuleManager::isEnabled($requiredModule)) {
            return false;
        }

        return true;
    }

    /**
     * Infer the module requirement for an item to keep sidebar visibility
     * synchronized with enabled modules without duplicating config rules.
     *
     * @param array<string, mixed> $item
     */
    protected static function resolveRequiredModule(array $item): ?string
    {
        if (!empty($item['module'])) {
            return (string) $item['module'];
        }

        if (!empty($item['match_module'])) {
            return (string) $item['match_module'];
        }

        if (!empty($item['parameters']['module'])) {
            return (string) $item['parameters']['module'];
        }

        $routeName = (string) ($item['route'] ?? '');

        return match ($routeName) {
            'users.index', 'users.manage', 'users.create', 'users.store', 'users.edit', 'users.update', 'users.toggle_active', 'roles.index', 'roles.manage' => 'users',
            'settings.index', 'settings.manage', 'settings.update' => 'settings',
            default => null,
        };
    }

    /**
     * @param array<string, mixed> $item
     * @return array<string, mixed>
     */
    protected static function normalizeItem(array $item, ?string $currentPage): array
    {
        $page = $item['legacy_page'] ?? null;
        $routeName = $item['route'] ?? null;
        $hasModuleMatch = !empty($item['match_module']);
        $active = $page !== null && $page === $currentPage;

        // When multiple items share the same route (ex: content.show),
        // defer active detection to match_module to avoid activating all items.
        if (!$active && is_string($routeName) && !$hasModuleMatch) {
            $active = request()->routeIs('admin.' . $routeName);
        }

        if (!$active && !empty($item['active_when'])) {
            foreach ((array) $item['active_when'] as $pattern) {
                if (request()->routeIs('admin.' . $pattern)) {
                    $active = true;
                    break;
                }
            }
        }

        if (!$active && !empty($item['match_module']) && request()->routeIs('admin.content.show')) {
            $active = request()->route('module') === $item['match_module'];
        }

        return [
            'label' => $item['label'] ?? 'Untitled',
            'icon' => $item['icon'] ?? 'bi bi-circle',
            'url' => self::resolveUrl($item),
            'active' => $active,
            'target' => $item['target'] ?? null,
            'badge' => $item['badge'] ?? null,
        ];
    }

    /**
     * @param array<string, mixed> $item
     */
    protected static function resolveUrl(array $item): ?string
    {
        if (!empty($item['legacy_page'])) {
            return admin_route('preview', ['page' => $item['legacy_page']]);
        }

        if (!empty($item['route'])) {
            $resolvedRoute = 'admin.' . (string) $item['route'];

            if (!Route::has($resolvedRoute)) {
                return null;
            }

            return admin_route((string) $item['route'], $item['parameters'] ?? []);
        }

        if (!empty($item['path'])) {
            return (string) $item['path'];
        }

        return null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected static function enabledModuleItems(): array
    {
        return ModuleManager::enabled()
            ->map(fn ($module) => [
                'label' => $module->name ?? ucfirst($module->slug),
                'icon' => 'bi bi-puzzle',
                'url' => admin_route('modules.index'),
                'active' => false,
                'target' => null,
                'badge' => $module->version ?? null,
            ])
            ->values()
            ->all();
    }
}
