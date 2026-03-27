<?php

namespace App\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class BackupService
{
    /**
     * @param array{with_db: bool, with_media: bool, with_extensions: bool, name: string|null} $options
     * @return array{ok: bool, backup_dir: string, created_files: array<int, string>, warnings: array<int, string>}
     */
    public static function create(array $options = []): array
    {
        $withDb = (bool) ($options['with_db'] ?? false);
        $withMedia = (bool) ($options['with_media'] ?? true);
        $withExtensions = (bool) ($options['with_extensions'] ?? true);
        $name = trim((string) ($options['name'] ?? ''));

        $suffix = $name !== '' ? '-' . preg_replace('/[^A-Za-z0-9_-]+/', '-', $name) : '';
        $backupDir = storage_path('app/backups/' . now()->format('Ymd-His') . $suffix);

        File::ensureDirectoryExists($backupDir);

        $createdFiles = [];
        $warnings = [];

        $manifest = [
            'format' => 'catmin.backup.v1',
            'created_at' => now()->toIso8601String(),
            'app' => [
                'name' => (string) config('app.name', 'CATMIN'),
                'url' => (string) config('app.url', ''),
                'env' => (string) config('app.env', 'production'),
            ],
            'options' => [
                'with_db' => $withDb,
                'with_media' => $withMedia,
                'with_extensions' => $withExtensions,
            ],
            'modules' => ModuleManager::summary(),
            'addons' => AddonManager::summary(),
        ];

        $manifestPath = $backupDir . '/manifest.json';
        File::put($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $createdFiles[] = $manifestPath;

        $settingsPath = $backupDir . '/settings-export.json';
        SettingsTransferService::exportToFile($settingsPath, true);
        $createdFiles[] = $settingsPath;

        if ($withMedia) {
            $mediaSource = storage_path('app/public/media');
            if (File::exists($mediaSource)) {
                $mediaTarget = $backupDir . '/media';
                File::copyDirectory($mediaSource, $mediaTarget);
                $createdFiles[] = $mediaTarget;
            } else {
                $warnings[] = 'Media non sauvegarde: dossier storage/app/public/media absent.';
            }
        }

        if ($withExtensions) {
            $addonsPath = base_path((string) config('catmin.addons.path', 'addons'));
            if (File::exists($addonsPath)) {
                $addonsTarget = $backupDir . '/addons';
                File::copyDirectory($addonsPath, $addonsTarget);
                $createdFiles[] = $addonsTarget;
            } else {
                $warnings[] = 'Dossier addons introuvable.';
            }
        }

        if ($withDb) {
            $dbDumpPath = $backupDir . '/database.sql';
            $dbResult = self::dumpDatabase($dbDumpPath);
            if ($dbResult['ok']) {
                $createdFiles[] = $dbDumpPath;
            }
            $warnings = array_merge($warnings, $dbResult['warnings']);
        }

        return [
            'ok' => true,
            'backup_dir' => $backupDir,
            'created_files' => $createdFiles,
            'warnings' => $warnings,
        ];
    }

    /**
     * @return array{ok: bool, warnings: array<int, string>}
     */
    protected static function dumpDatabase(string $targetPath): array
    {
        $warnings = [];
        $defaultConnection = (string) Config::get('database.default', '');
        $driver = (string) Config::get("database.connections.{$defaultConnection}.driver", '');

        if ($driver !== 'mysql') {
            return [
                'ok' => false,
                'warnings' => ['DB dump auto supporte uniquement pour MySQL en V1.'],
            ];
        }

        $host = (string) Config::get("database.connections.{$defaultConnection}.host", '127.0.0.1');
        $port = (string) Config::get("database.connections.{$defaultConnection}.port", '3306');
        $database = (string) Config::get("database.connections.{$defaultConnection}.database", '');
        $username = (string) Config::get("database.connections.{$defaultConnection}.username", '');
        $password = (string) Config::get("database.connections.{$defaultConnection}.password", '');

        if ($database === '' || $username === '') {
            return [
                'ok' => false,
                'warnings' => ['DB dump annule: configuration base incomplete.'],
            ];
        }

        $command = [
            'mysqldump',
            '--single-transaction',
            '--quick',
            '--host=' . $host,
            '--port=' . $port,
            '--user=' . $username,
        ];

        if ($password !== '') {
            $command[] = '--password=' . $password;
        }

        $command[] = $database;

        $process = new Process($command);
        $process->setTimeout(120);
        $process->run();

        if (!$process->isSuccessful()) {
            return [
                'ok' => false,
                'warnings' => ['DB dump echoue: mysqldump absent ou inaccessible.'],
            ];
        }

        File::put($targetPath, (string) $process->getOutput());

        return [
            'ok' => true,
            'warnings' => $warnings,
        ];
    }
}
