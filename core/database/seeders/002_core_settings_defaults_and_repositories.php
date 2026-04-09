<?php

declare(strict_types=1);

require_once CATMIN_CORE . '/settings-schema.php';
require_once CATMIN_CORE . '/module-repository-repository.php';

return static function (\PDO $pdo, array $prefixes): void {
    $core = (string) ($prefixes['core'] ?? 'core_');
    $settingsTable = $core . 'settings';

    try {
        $tableCheck = $pdo->query("SELECT 1 FROM {$settingsTable} LIMIT 1");
        if ($tableCheck !== false) {
            /** @var array<string,array<string,mixed>> $defaults */
            $defaults = \CoreSettingsSchema::defaults();
            $find = $pdo->prepare('SELECT id FROM ' . $settingsTable . ' WHERE category = :category AND setting_key = :setting_key LIMIT 1');
            $insert = $pdo->prepare(
                'INSERT INTO ' . $settingsTable . ' (category, setting_key, setting_value, is_public, updated_at) VALUES (:category, :setting_key, :setting_value, :is_public, CURRENT_TIMESTAMP)'
            );

            foreach ($defaults as $fullKey => $meta) {
                $fullKey = trim((string) $fullKey);
                if ($fullKey === '' || !str_contains($fullKey, '.')) {
                    continue;
                }
                [$category, $key] = explode('.', $fullKey, 2);
                $category = trim((string) $category);
                $key = trim((string) $key);
                if ($category === '' || $key === '') {
                    continue;
                }

                $find->execute([
                    'category' => $category,
                    'setting_key' => $key,
                ]);
                if ($find->fetchColumn() !== false) {
                    continue;
                }

                $defaultValue = $meta['default'] ?? '';
                if (is_bool($defaultValue)) {
                    $settingValue = $defaultValue ? '1' : '0';
                } elseif (is_array($defaultValue)) {
                    $settingValue = (string) (json_encode($defaultValue, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]');
                } elseif ($defaultValue === null) {
                    $settingValue = '';
                } else {
                    $settingValue = (string) $defaultValue;
                }

                $isPublic = !empty($meta['system']) ? 0 : (!empty($meta['protected']) ? 0 : 1);
                $insert->execute([
                    'category' => $category,
                    'setting_key' => $key,
                    'setting_value' => $settingValue,
                    'is_public' => (int) $isPublic,
                ]);
            }
        }
    } catch (\Throwable) {
        // Seeder must remain non-blocking.
    }

    try {
        $repositories = new \CoreModuleRepositoryRepository();
        $repositories->listAll();
        $policy = $repositories->loadPolicy();
        $repositories->savePolicy($policy);
    } catch (\Throwable) {
        // Seeder must remain non-blocking.
    }
};

