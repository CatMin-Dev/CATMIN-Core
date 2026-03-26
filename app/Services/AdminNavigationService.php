<?php

namespace App\Services;

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

        if (!empty($item['module']) && !ModuleManager::isEnabled((string) $item['module'])) {
            return false;
        }

        return true;
    }

    /**
     * @param array<string, mixed> $item
     * @return array<string, mixed>
     */
    protected static function normalizeItem(array $item, ?string $currentPage): array
    {
        $page = $item['legacy_page'] ?? null;

        return [
            'label' => $item['label'] ?? 'Untitled',
            'icon' => $item['icon'] ?? 'bi bi-circle',
            'url' => self::resolveUrl($item),
            'active' => $page !== null && $page === $currentPage,
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
                'url' => admin_route('bridge'),
                'active' => false,
                'target' => null,
                'badge' => $module->version ?? null,
            ])
            ->values()
            ->all();
    }
}
