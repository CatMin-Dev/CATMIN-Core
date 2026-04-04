<?php

namespace App\Services\AdminNavigation;

use App\Services\AddonManager;
use App\Services\ModuleManager;
use App\Services\RbacPermissionService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

class AdminNavigationBuilder
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function buildRawItems(): array
    {
        $sections = (array) config('catmin.navigation.sections', []);
        $sections = $this->withAddonNavigation($sections);

        $items = [];
        foreach ($sections as $section) {
            if (($section['source'] ?? null) === 'enabled_modules') {
                continue;
            }

            foreach ((array) ($section['items'] ?? []) as $item) {
                if (!is_array($item) || !$this->itemIsVisible($item)) {
                    continue;
                }

                $url = $this->resolveUrl($item);
                if ($url === null || $url === '') {
                    continue;
                }

                $items[] = [
                    'id' => (string) ($item['id'] ?? md5((string) ($item['label'] ?? $url))),
                    'label' => (string) ($item['label'] ?? 'Untitled'),
                    'icon' => (string) ($item['icon'] ?? 'bi bi-circle'),
                    'url' => $url,
                    'route' => isset($item['route']) ? 'admin.' . (string) $item['route'] : null,
                    'active_when' => array_values((array) ($item['active_when'] ?? [])),
                    'permission' => $item['permission'] ?? null,
                    'module' => $this->resolveRequiredModule($item),
                    'target' => $item['target'] ?? null,
                    'badge' => $item['badge'] ?? null,
                    'section' => (string) ($section['title'] ?? 'Navigation'),
                    'match_module' => $item['match_module'] ?? null,
                ];
            }
        }

        return $items;
    }

    /**
     * @param array<int, array<string, mixed>> $sections
     * @return array<int, array<string, mixed>>
     */
    private function withAddonNavigation(array $sections): array
    {
        $addonItems = [];

        foreach (AddonManager::enabled() as $addon) {
            $configPath = (string) ($addon->path ?? '') . '/config.php';
            if ($configPath === '' || !File::exists($configPath)) {
                continue;
            }

            try {
                $config = require $configPath;
            } catch (\Throwable) {
                continue;
            }

            foreach ((array) ($config['navigation_items'] ?? []) as $item) {
                if (!is_array($item)) {
                    continue;
                }

                $item['required_addon'] = (string) ($addon->slug ?? '');
                $item['category'] = $item['category'] ?? 'Business / Addons';
                $addonItems[] = $item;
            }
        }

        if ($addonItems === []) {
            return $sections;
        }

        foreach ($sections as $index => $section) {
            if ((string) ($section['title'] ?? '') !== 'Intégrations') {
                continue;
            }

            $section['items'] = array_values(array_merge((array) ($section['items'] ?? []), $addonItems));
            $sections[$index] = $section;

            return $sections;
        }

        $sections[] = ['title' => 'Intégrations', 'items' => $addonItems];

        return $sections;
    }

    /**
     * @param array<string, mixed> $item
     */
    private function itemIsVisible(array $item): bool
    {
        if (!empty($item['feature']) && !config('catmin.features.' . $item['feature'], false)) {
            return false;
        }

        if (!empty($item['permission']) && !RbacPermissionService::allows(request(), (string) $item['permission'])) {
            return false;
        }

        if (!empty($item['required_addon']) && !AddonManager::enabled()->firstWhere('slug', (string) $item['required_addon'])) {
            return false;
        }

        $requiredModule = $this->resolveRequiredModule($item);
        if ($requiredModule !== null && !ModuleManager::isEnabled($requiredModule)) {
            return false;
        }

        return true;
    }

    /**
     * @param array<string, mixed> $item
     */
    private function resolveUrl(array $item): ?string
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
     * @param array<string, mixed> $item
     */
    private function resolveRequiredModule(array $item): ?string
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

        return match ((string) ($item['route'] ?? '')) {
            'users.index', 'users.manage', 'users.create', 'users.store', 'users.edit', 'users.update', 'users.toggle_active', 'roles.index', 'roles.manage' => 'users',
            'settings.index', 'settings.manage', 'settings.update' => 'settings',
            default => null,
        };
    }
}
