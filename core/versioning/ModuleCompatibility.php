<?php

declare(strict_types=1);

namespace Core\versioning;

require_once CATMIN_CORE . '/module-loader.php';

final class ModuleCompatibility
{
    public function report(): array
    {
        $loader = new \CoreModuleLoader();
        $snapshot = $loader->scan();
        $rows = [];
        $blocked = [];

        foreach ((array) ($snapshot['modules'] ?? []) as $module) {
            $manifest = (array) ($module['manifest'] ?? []);
            $slug = (string) ($manifest['slug'] ?? 'unknown');
            $row = [
                'slug' => $slug,
                'type' => (string) ($manifest['type'] ?? 'unknown'),
                'version' => (string) ($manifest['version'] ?? '0.0.0'),
                'enabled' => (bool) ($module['enabled'] ?? false),
                'compatible' => (bool) ($module['compatible'] ?? false),
                'valid' => (bool) ($module['valid'] ?? false),
                'state' => (string) ($module['state'] ?? 'detected'),
                'errors' => array_values((array) ($module['errors'] ?? [])),
            ];
            $rows[] = $row;
            if ($row['enabled'] && (!$row['compatible'] || !$row['valid'])) {
                $blocked[] = $row;
            }
        }

        return [
            'modules' => $rows,
            'has_blocking' => $blocked !== [],
            'blocking' => $blocked,
        ];
    }
}

