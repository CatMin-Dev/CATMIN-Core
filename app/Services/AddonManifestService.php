<?php

namespace App\Services;

use stdClass;

class AddonManifestService
{
    /**
     * @param array<string, mixed>|stdClass $manifest
     * @return array<string, mixed>
     */
    public static function normalize(array|stdClass $manifest): array
    {
        $data = $manifest instanceof stdClass ? (array) $manifest : $manifest;

        $name = trim((string) ($data['name'] ?? ''));
        $slug = trim((string) ($data['slug'] ?? ''));
        $versionRaw = trim((string) ($data['version'] ?? ''));
        $version = VersioningService::normalize($versionRaw);
        $requiredModules = self::normalizeStringList($data['required_modules'] ?? $data['depends_modules'] ?? []);
        $addonDependencies = self::normalizeStringList($data['dependencies'] ?? []);
        $permissions = self::normalizeStringList($data['permissions_declared'] ?? $data['permissions'] ?? []);

        if ((bool) ($data['requires_core'] ?? true) && !in_array('core', $requiredModules, true)) {
            $requiredModules[] = 'core';
        }

        return [
            'name' => $name,
            'slug' => $slug,
            'description' => trim((string) ($data['description'] ?? '')),
            'version' => $version,
            'version_raw' => $versionRaw,
            'version_valid' => VersioningService::isValid($versionRaw),
            'author' => trim((string) ($data['author'] ?? 'CATMIN Team')),
            'category' => trim((string) ($data['category'] ?? 'general')),
            'enabled' => (bool) ($data['enabled'] ?? false),
            'dependencies' => $addonDependencies,
            'required_core_version' => trim((string) ($data['required_core_version'] ?? '')),
            'required_php_version' => trim((string) ($data['required_php_version'] ?? '8.2.0')),
            'required_modules' => array_values(array_unique($requiredModules)),
            'has_routes' => (bool) ($data['has_routes'] ?? $data['routes'] ?? true),
            'has_migrations' => (bool) ($data['has_migrations'] ?? true),
            'has_assets' => (bool) ($data['has_assets'] ?? true),
            'has_views' => (bool) ($data['has_views'] ?? true),
            'has_events' => (bool) ($data['has_events'] ?? !empty($data['events_emitted']) || !empty($data['events_listens']) || !empty($data['ui_hooks'])),
            'entrypoints' => is_array($data['entrypoints'] ?? null) ? $data['entrypoints'] : [],
            'checksum' => trim((string) ($data['checksum'] ?? '')),
            'homepage' => trim((string) ($data['homepage'] ?? '')),
            'docs_url' => trim((string) ($data['docs_url'] ?? '')),
            'changelog' => trim((string) ($data['changelog'] ?? '')),
            'compatibility' => is_array($data['compatibility'] ?? null) ? $data['compatibility'] : [],
            'install_notes' => trim((string) ($data['install_notes'] ?? '')),
            'permissions_declared' => $permissions,
            'events_emitted' => self::normalizeStringList($data['events_emitted'] ?? []),
            'events_listens' => self::normalizeStringList($data['events_listens'] ?? []),
            'ui_hooks' => self::normalizeStringList($data['ui_hooks'] ?? []),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function decodeJson(string $json): ?array
    {
        $decoded = json_decode($json, true);

        return is_array($decoded) ? self::normalize($decoded) : null;
    }

    public static function isManifestValid(array $manifest): bool
    {
        return $manifest['name'] !== ''
            && $manifest['slug'] !== ''
            && (bool) ($manifest['version_valid'] ?? false);
    }

    public static function normalizeCoreVersion(string $version): string
    {
        $value = trim(strtolower($version));
        $value = preg_replace('/^v/', '', $value) ?? $value;
        $value = preg_replace('/-.*$/', '', $value) ?? $value;

        if ($value === '') {
            return '0.0.0';
        }

        if (preg_match('/^\d+$/', $value)) {
            return $value . '.0.0';
        }

        if (preg_match('/^\d+\.\d+$/', $value)) {
            return $value . '.0';
        }

        return preg_match('/^\d+\.\d+\.\d+$/', $value) ? $value : '0.0.0';
    }

    /**
     * @param mixed $value
     * @return array<int, string>
     */
    private static function normalizeStringList(mixed $value): array
    {
        $items = is_array($value) ? $value : (is_string($value) ? explode(',', $value) : []);

        return array_values(array_unique(array_filter(array_map(
            static fn ($item) => trim(strtolower((string) $item)),
            $items
        ), static fn ($item) => $item !== '')));
    }
}
