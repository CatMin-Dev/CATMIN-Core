<?php

declare(strict_types=1);

final class CoreModuleManifestStandard
{
    private const TYPE_ENUM = ['core', 'admin', 'front', 'integration', 'driver'];
    private const CHANNEL_ENUM = ['stable', 'beta', 'alpha', 'experimental'];
    private const CATEGORY_ENUM = ['content', 'seo', 'media', 'system', 'security', 'marketing', 'commerce', 'integration', 'custom'];

    public function normalize(array $manifest): array
    {
        $dependenciesRaw = $manifest['dependencies'] ?? [];
        if (is_array($dependenciesRaw) && array_is_list($dependenciesRaw)) {
            $dependenciesRaw = [
                'requires' => $dependenciesRaw,
                'conflicts' => [],
                'replaces' => [],
            ];
        }
        if (!is_array($dependenciesRaw)) {
            $dependenciesRaw = ['requires' => [], 'conflicts' => [], 'replaces' => []];
        }

        $loadRaw = $manifest['load'] ?? [];
        if (!is_array($loadRaw)) {
            $loadRaw = [];
        }

        $normalized = [
            'module_schema_version' => trim((string) ($manifest['module_schema_version'] ?? '1.0.0')),
            'name' => trim((string) ($manifest['name'] ?? '')),
            'display_name' => trim((string) ($manifest['display_name'] ?? ($manifest['name'] ?? ''))),
            'slug' => strtolower(trim((string) ($manifest['slug'] ?? ''))),
            'type' => strtolower(trim((string) ($manifest['type'] ?? ''))),
            'version' => trim((string) ($manifest['version'] ?? '')),
            'description' => trim((string) ($manifest['description'] ?? '')),
            'summary' => trim((string) ($manifest['summary'] ?? '')),
            'author' => trim((string) ($manifest['author'] ?? '')),
            'homepage' => trim((string) ($manifest['homepage'] ?? '')),
            'support_url' => trim((string) ($manifest['support_url'] ?? '')),
            'docs_url' => trim((string) ($manifest['docs_url'] ?? '')),
            'readme_url' => trim((string) ($manifest['readme_url'] ?? '')),
            'changelog_url' => trim((string) ($manifest['changelog_url'] ?? '')),
            'keywords' => is_array($manifest['keywords'] ?? null) ? array_values(array_filter(array_map(static fn ($k): string => trim((string) $k), (array) $manifest['keywords']), static fn (string $k): bool => $k !== '')) : [],
            'category' => strtolower(trim((string) ($manifest['category'] ?? 'custom'))),
            'icon' => trim((string) ($manifest['icon'] ?? '')),
            'enabled_by_default' => array_key_exists('enabled_by_default', $manifest)
                ? (bool) $manifest['enabled_by_default']
                : ((bool) ($manifest['enabled'] ?? false)),
            'installable' => array_key_exists('installable', $manifest) ? (bool) $manifest['installable'] : true,
            'autoload' => array_key_exists('autoload', $manifest) ? (bool) $manifest['autoload'] : true,
            'show_in_manager' => array_key_exists('show_in_manager', $manifest) ? (bool) $manifest['show_in_manager'] : true,
            'show_in_market' => array_key_exists('show_in_market', $manifest) ? (bool) $manifest['show_in_market'] : true,
            'release_channel' => strtolower(trim((string) ($manifest['release_channel'] ?? 'stable'))),
            'php_min' => trim((string) ($manifest['php_min'] ?? '')),
            'catmin_min' => trim((string) ($manifest['catmin_min'] ?? '')),
            'catmin_max' => trim((string) ($manifest['catmin_max'] ?? '')),
            'trusted_module' => array_key_exists('trusted_module', $manifest) ? (bool) $manifest['trusted_module'] : ((bool) ($manifest['core_compatible'] ?? false)),
            'requires_reauth_for_admin_actions' => (bool) ($manifest['requires_reauth_for_admin_actions'] ?? false),
            'load' => [
                'routes' => array_key_exists('routes', $loadRaw) ? (bool) $loadRaw['routes'] : true,
                'views' => array_key_exists('views', $loadRaw) ? (bool) $loadRaw['views'] : true,
                'assets' => array_key_exists('assets', $loadRaw) ? (bool) $loadRaw['assets'] : true,
                'migrations' => array_key_exists('migrations', $loadRaw) ? (bool) $loadRaw['migrations'] : true,
                'hooks' => array_key_exists('hooks', $loadRaw) ? (bool) $loadRaw['hooks'] : true,
                'translations' => array_key_exists('translations', $loadRaw) ? (bool) $loadRaw['translations'] : true,
                'permissions' => array_key_exists('permissions', $loadRaw) ? (bool) $loadRaw['permissions'] : true,
                'settings' => array_key_exists('settings', $loadRaw) ? (bool) $loadRaw['settings'] : true,
            ],
            'dependencies' => [
                'requires' => $this->normalizeSlugList($dependenciesRaw['requires'] ?? []),
                'conflicts' => $this->normalizeSlugList($dependenciesRaw['conflicts'] ?? []),
                'replaces' => $this->normalizeSlugList($dependenciesRaw['replaces'] ?? []),
            ],
            'maintainers' => is_array($manifest['maintainers'] ?? null) ? array_values($manifest['maintainers']) : [],
            'tags' => is_array($manifest['tags'] ?? null) ? array_values(array_filter(array_map(static fn ($v): string => trim((string) $v), (array) $manifest['tags']), static fn (string $v): bool => $v !== '')) : [],
        ];

        return $normalized;
    }

    public function validate(array $normalized): array
    {
        $errors = [];

        foreach (['name', 'slug', 'type', 'version', 'description', 'author', 'php_min', 'catmin_min'] as $required) {
            if (trim((string) ($normalized[$required] ?? '')) === '') {
                $errors[] = 'Champ manifest manquant: ' . $required;
            }
        }

        $slug = (string) ($normalized['slug'] ?? '');
        if ($slug === '' || preg_match('/^[a-z0-9][a-z0-9-]*$/', $slug) !== 1) {
            $errors[] = 'Slug invalide';
        }

        $type = (string) ($normalized['type'] ?? '');
        if (!in_array($type, self::TYPE_ENUM, true)) {
            $errors[] = 'Type invalide: ' . $type;
        }

        $version = (string) ($normalized['version'] ?? '');
        if ($version === '' || preg_match('/^[0-9]+\.[0-9]+\.[0-9]+(?:[-+][a-zA-Z0-9.-]+)?$/', $version) !== 1) {
            $errors[] = 'Version invalide';
        }

        $phpMin = (string) ($normalized['php_min'] ?? '');
        if ($phpMin !== '' && preg_match('/^[0-9]+\.[0-9]+(?:\.[0-9]+)?$/', $phpMin) !== 1) {
            $errors[] = 'php_min invalide';
        }
        $catminMin = (string) ($normalized['catmin_min'] ?? '');
        if ($catminMin !== '' && preg_match('/^[0-9]+\.[0-9]+\.[0-9]+(?:[-+][a-zA-Z0-9.-]+)?$/', $catminMin) !== 1) {
            $errors[] = 'catmin_min invalide';
        }

        $catminMax = (string) ($normalized['catmin_max'] ?? '');
        if ($catminMax !== '' && !$this->isValidCatminMax($catminMax)) {
            $errors[] = 'catmin_max invalide';
        }

        $category = (string) ($normalized['category'] ?? '');
        if ($category !== '' && !in_array($category, self::CATEGORY_ENUM, true)) {
            $errors[] = 'Category invalide: ' . $category;
        }

        $channel = (string) ($normalized['release_channel'] ?? '');
        if ($channel !== '' && !in_array($channel, self::CHANNEL_ENUM, true)) {
            $errors[] = 'Release channel invalide: ' . $channel;
        }

        foreach (['homepage', 'support_url', 'docs_url', 'readme_url', 'changelog_url'] as $urlField) {
            $url = trim((string) ($normalized[$urlField] ?? ''));
            if ($url !== '' && filter_var($url, FILTER_VALIDATE_URL) === false) {
                $errors[] = 'URL invalide: ' . $urlField;
            }
        }

        return ['valid' => $errors === [], 'errors' => $errors];
    }

    private function normalizeSlugList(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }
        $rows = [];
        foreach ($value as $entry) {
            $slug = strtolower(trim((string) $entry));
            if ($slug === '' || preg_match('/^[a-z0-9][a-z0-9-]*$/', $slug) !== 1) {
                continue;
            }
            $rows[] = $slug;
        }
        return array_values(array_unique($rows));
    }

    private function isValidCatminMax(string $value): bool
    {
        if (preg_match('/^[0-9]+\.[0-9]+\.[0-9]+(?:[-+][a-zA-Z0-9.-]+)?$/', $value) === 1) {
            return true;
        }
        return preg_match('/^[0-9]+\.x$/', $value) === 1;
    }
}

