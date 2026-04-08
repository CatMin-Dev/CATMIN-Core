<?php

declare(strict_types=1);

require_once CATMIN_CORE . '/market-github.php';
require_once CATMIN_CORE . '/market-installer.php';
require_once CATMIN_CORE . '/module-loader.php';
require_once CATMIN_CORE . '/module-compatibility-checker.php';

final class CoreMarketEngine
{
    public function catalog(): array
    {
        $remote = (new CoreMarketGithub())->catalog();
        if (!(bool) ($remote['ok'] ?? false)) {
            return [
                'ok' => false,
                'error' => (string) ($remote['error'] ?? 'Catalogue indisponible.'),
                'items' => [],
            ];
        }

        $localBySlug = $this->localModulesBySlug();
        $items = [];

        foreach ((array) ($remote['items'] ?? []) as $item) {
            if (!is_array($item)) {
                continue;
            }
            $slug = strtolower(trim((string) ($item['slug'] ?? '')));
            $local = $slug !== '' ? ($localBySlug[$slug] ?? null) : null;
            $manifest = is_array($item['manifest'] ?? null) ? $item['manifest'] : [];
            $compat = (new CoreModuleCompatibilityChecker())->check($manifest);

            $items[] = [
                'scope' => (string) ($item['scope'] ?? 'unknown'),
                'slug' => $slug !== '' ? $slug : 'unknown',
                'name' => (string) ($item['name'] ?? strtoupper($slug !== '' ? $slug : 'module')),
                'description' => (string) ($item['description'] ?? ''),
                'version' => (string) ($item['version'] ?? '0.0.0'),
                'catmin_min' => (string) ($item['catmin_min'] ?? ''),
                'catmin_max' => (string) ($item['catmin_max'] ?? ''),
                'zip_url' => (string) ($item['zip_url'] ?? ''),
                'path_in_zip' => (string) ($item['path_in_zip'] ?? ''),
                'manifest' => $manifest,
                'installed' => is_array($local),
                'enabled' => (bool) ($local['enabled'] ?? false),
                'installed_version' => is_array($local) ? (string) ($local['version'] ?? '0.0.0') : '',
                'has_update' => is_array($local) ? version_compare((string) ($item['version'] ?? '0.0.0'), (string) ($local['version'] ?? '0.0.0'), '>') : false,
                'compatible' => (bool) ($compat['compatible'] ?? false),
                'compat_errors' => (array) ($compat['errors'] ?? []),
            ];
        }

        usort($items, static fn (array $a, array $b): int => strcmp((string) ($a['scope'] . '/' . $a['slug']), (string) ($b['scope'] . '/' . $b['slug'])));

        return [
            'ok' => true,
            'error' => '',
            'items' => $items,
            'stats' => [
                'total' => count($items),
                'installed' => count(array_filter($items, static fn (array $row): bool => (bool) ($row['installed'] ?? false))),
                'updates' => count(array_filter($items, static fn (array $row): bool => (bool) ($row['has_update'] ?? false))),
                'incompatible' => count(array_filter($items, static fn (array $row): bool => !((bool) ($row['compatible'] ?? true)))),
            ],
        ];
    }

    public function install(array $catalogItem): array
    {
        return (new CoreMarketInstaller())->installFromCatalogItem($catalogItem);
    }

    private function localModulesBySlug(): array
    {
        $scan = (new CoreModuleLoader())->scan();
        $rows = [];
        foreach ((array) ($scan['modules'] ?? []) as $module) {
            if (!is_array($module)) {
                continue;
            }
            $manifest = is_array($module['manifest'] ?? null) ? $module['manifest'] : [];
            $slug = strtolower(trim((string) ($manifest['slug'] ?? '')));
            if ($slug === '') {
                continue;
            }
            $rows[$slug] = [
                'slug' => $slug,
                'scope' => strtolower(trim((string) ($manifest['type'] ?? ''))),
                'version' => (string) ($manifest['version'] ?? '0.0.0'),
                'enabled' => (bool) ($module['enabled'] ?? false),
            ];
        }
        return $rows;
    }
}

