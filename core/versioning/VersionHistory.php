<?php

declare(strict_types=1);

namespace Core\versioning;

use Core\config\Config;
require_once CATMIN_CORE . '/db-version-manager.php';

final class VersionHistory
{
    public static function syncCurrentVersion(): void
    {
        try {
            $version = trim(Version::current());
            if ($version === '') {
                return;
            }

            $historyFile = (string) Config::get('versioning.history_file', base_path('logs/version-history.json'));
            if ($historyFile === '') {
                return;
            }

            $dir = dirname($historyFile);
            if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
                return;
            }

            $history = [];
            if (is_file($historyFile)) {
                $raw = file_get_contents($historyFile);
                $decoded = is_string($raw) ? json_decode($raw, true) : null;
                if (is_array($decoded)) {
                    $history = $decoded;
                }
            }

            $latest = is_array($history[0] ?? null) ? $history[0] : null;
            $latestVersion = trim((string) ($latest['version'] ?? ''));
            if ($latestVersion === $version) {
                return;
            }

            $history[] = [
                'version' => $version,
                'date' => date('Y-m-d'),
                'scope' => 'core',
                'db_version' => (new \CoreDbVersionManager())->currentSchemaVersion(),
                'changes' => ['auto version sync from version.json'],
                'prompt' => 'system-auto',
            ];

            usort(
                $history,
                static fn (array $a, array $b): int => strcmp((string) ($b['date'] ?? ''), (string) ($a['date'] ?? ''))
            );

            $encoded = json_encode($history, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if (!is_string($encoded)) {
                return;
            }

            @file_put_contents($historyFile, $encoded . PHP_EOL, LOCK_EX);
        } catch (\Throwable) {
        }
    }
}
