<?php

declare(strict_types=1);

require_once CATMIN_CORE . '/updater.php';
require_once CATMIN_CORE . '/module-loader.php';
require_once CATMIN_CORE . '/module-integrity-scanner.php';
require_once CATMIN_CORE . '/market-engine.php';

final class CoreUpdateCenter
{
    public function buildSnapshot(): array
    {
        $core = (new CoreUpdater())->check();
        $catalog = (new CoreMarketEngine())->catalog();
        $loader = new CoreModuleLoader();
        $scan = $loader->scan();
        $integrityReport = (new CoreModuleIntegrityScanner())->scanAll(true);
        $integrityBySlug = [];
        foreach ((array) ($integrityReport['modules'] ?? []) as $row) {
            $slug = strtolower(trim((string) ($row['slug'] ?? '')));
            if ($slug !== '') {
                $integrityBySlug[$slug] = $row;
            }
        }

        $localBySlug = [];
        foreach ((array) ($scan['modules'] ?? []) as $module) {
            $manifest = (array) ($module['manifest'] ?? []);
            $slug = strtolower(trim((string) ($manifest['slug'] ?? '')));
            if ($slug === '') {
                continue;
            }
            $localBySlug[$slug] = [
                'slug' => $slug,
                'type' => (string) ($manifest['type'] ?? ''),
                'name' => (string) ($manifest['display_name'] ?? $manifest['name'] ?? $slug),
                'local_version' => (string) ($manifest['version'] ?? '0.0.0'),
                'enabled' => (bool) ($module['enabled'] ?? false),
                'compatible' => (bool) ($module['compatible'] ?? true),
                'errors' => (array) ($module['errors'] ?? []),
                'integrity' => $integrityBySlug[$slug] ?? null,
            ];
        }

        $moduleUpdates = [];
        if ((bool) ($catalog['ok'] ?? false)) {
            foreach ((array) ($catalog['items'] ?? []) as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $slug = strtolower(trim((string) ($item['slug'] ?? '')));
                if ($slug === '') {
                    continue;
                }
                $local = $localBySlug[$slug] ?? null;
                if (!is_array($local)) {
                    continue;
                }
                $remoteVersion = (string) ($item['version'] ?? '0.0.0');
                $hasUpdate = version_compare($remoteVersion, (string) ($local['local_version'] ?? '0.0.0'), '>');
                $moduleUpdates[] = [
                    'slug' => $slug,
                    'name' => (string) ($item['name'] ?? $local['name']),
                    'type' => (string) ($item['scope'] ?? $local['type']),
                    'local_version' => (string) $local['local_version'],
                    'remote_version' => $remoteVersion,
                    'has_update' => $hasUpdate,
                    'compatible' => (bool) ($item['compatible'] ?? $local['compatible'] ?? true),
                    'integrity_status' => (string) (($local['integrity']['integrity_status'] ?? 'unknown')),
                    'signature_status' => (string) (($local['integrity']['signature_status'] ?? 'unknown')),
                    'trusted' => (bool) (($local['integrity']['trusted'] ?? false)),
                    'state' => (string) ($local['enabled'] ? 'enabled' : 'disabled'),
                ];
            }
        }

        usort($moduleUpdates, static function (array $a, array $b): int {
            if (($a['has_update'] ?? false) !== ($b['has_update'] ?? false)) {
                return ($a['has_update'] ?? false) ? -1 : 1;
            }
            return strcmp((string) ($a['slug'] ?? ''), (string) ($b['slug'] ?? ''));
        });

        $backup = $this->latestBackup();
        $history = $this->history();

        $modulesWithUpdates = count(array_filter($moduleUpdates, static fn (array $row): bool => (bool) ($row['has_update'] ?? false)));
        $trustAlerts = count(array_filter($moduleUpdates, static fn (array $row): bool => !((bool) ($row['trusted'] ?? false))));
        $coreUpdateAvailable = (bool) ($core['update_available'] ?? false);

        return [
            'core' => $core,
            'modules' => $moduleUpdates,
            'backup' => $backup,
            'history' => $history,
            'integrity_report' => $integrityReport,
            'summary' => [
                'core_update_available' => $coreUpdateAvailable,
                'modules_with_updates' => $modulesWithUpdates,
                'trust_alerts' => $trustAlerts,
                'last_check_at' => gmdate('c'),
                'last_update_ok_at' => (string) ($history['last_success_at'] ?? ''),
            ],
        ];
    }

    private function latestBackup(): array
    {
        $dir = CATMIN_STORAGE . '/updates/backups';
        if (!is_dir($dir)) {
            return ['exists' => false, 'file' => '', 'time' => ''];
        }
        $files = glob($dir . '/*.zip') ?: [];
        rsort($files);
        $last = $files[0] ?? '';
        if (!is_string($last) || $last === '') {
            return ['exists' => false, 'file' => '', 'time' => ''];
        }
        return [
            'exists' => true,
            'file' => basename($last),
            'time' => gmdate('c', (int) filemtime($last)),
            'path' => $last,
        ];
    }

    private function history(): array
    {
        $dir = CATMIN_STORAGE . '/updates/reports';
        if (!is_dir($dir)) {
            return ['items' => [], 'last_success_at' => ''];
        }
        $files = glob($dir . '/*.json') ?: [];
        rsort($files);

        $items = [];
        $lastSuccessAt = '';
        foreach (array_slice($files, 0, 15) as $file) {
            $decoded = json_decode((string) file_get_contents($file), true);
            if (!is_array($decoded)) {
                continue;
            }
            $ok = (bool) ($decoded['ok'] ?? false);
            $at = (string) ($decoded['started_at'] ?? '');
            if ($ok && $lastSuccessAt === '' && $at !== '') {
                $lastSuccessAt = $at;
            }
            $items[] = [
                'file' => basename($file),
                'ok' => $ok,
                'started_at' => $at,
                'dry_run' => (bool) ($decoded['dry_run'] ?? false),
                'error' => (string) ($decoded['error'] ?? ''),
            ];
        }
        return ['items' => $items, 'last_success_at' => $lastSuccessAt];
    }
}

