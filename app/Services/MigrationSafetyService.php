<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class MigrationSafetyService
{
    private const LOG_FILE = 'logs/migration-safety.jsonl';

    /**
     * @param array{dry_run?: bool, skip_core_migrate?: bool, rollback_on_fail?: bool, backup_name?: string} $options
     * @return array<string, mixed>
     */
    public function run(array $options = []): array
    {
        $dryRun = (bool) ($options['dry_run'] ?? false);
        $skipCoreMigrate = (bool) ($options['skip_core_migrate'] ?? false);
        $rollbackOnFail = (bool) ($options['rollback_on_fail'] ?? true);
        $backupName = (string) ($options['backup_name'] ?? ('migrate-safe-' . now()->format('Ymd-His')));

        $actions = [
            $skipCoreMigrate ? null : 'php artisan migrate --force',
            'php artisan catmin:migrate:extensions',
            'php artisan cache:clear',
            'php artisan config:clear',
            'php artisan view:clear',
            'php artisan route:clear',
        ];
        $actions = array_values(array_filter($actions));

        if ($dryRun) {
            $this->log('dry_run', ['actions' => $actions]);

            return [
                'ok' => true,
                'dry_run' => true,
                'actions' => $actions,
                'message' => 'Dry-run: aucune migration executee.',
            ];
        }

        $backup = BackupService::create([
            'with_db' => true,
            'with_media' => false,
            'with_extensions' => false,
            'name' => $backupName,
        ]);

        if (($backup['ok'] ?? false) !== true) {
            return [
                'ok' => false,
                'message' => 'Backup pre-migration impossible.',
                'actions' => $actions,
            ];
        }

        $backupDir = (string) ($backup['backup_dir'] ?? '');

        $this->log('migration_started', [
            'actions' => $actions,
            'backup_dir' => $backupDir,
            'rollback_on_fail' => $rollbackOnFail,
        ]);

        try {
            if (!$skipCoreMigrate) {
                Artisan::call('migrate', ['--force' => true]);
            }

            $exit = Artisan::call('catmin:migrate:extensions');
            if ($exit !== 0) {
                throw new \RuntimeException('catmin:migrate:extensions a echoue.');
            }

            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('view:clear');
            Artisan::call('route:clear');

            $this->log('migration_success', [
                'backup_dir' => $backupDir,
            ]);

            return [
                'ok' => true,
                'dry_run' => false,
                'actions' => $actions,
                'backup_dir' => $backupDir,
                'message' => 'Migrations appliquees avec succes.',
            ];
        } catch (\Throwable $e) {
            $rollback = ['ok' => false, 'message' => 'Rollback non execute.'];

            if ($rollbackOnFail) {
                $rollback = $this->rollbackDatabase($backupDir);
            }

            $this->log('migration_failed', [
                'error' => $e->getMessage(),
                'backup_dir' => $backupDir,
                'rollback' => $rollback,
            ]);

            return [
                'ok' => false,
                'dry_run' => false,
                'actions' => $actions,
                'backup_dir' => $backupDir,
                'error' => $e->getMessage(),
                'rollback' => $rollback,
                'message' => 'Echec migration' . ($rollbackOnFail ? ' avec tentative de rollback.' : '.'),
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rollbackDatabase(string $backupDir): array
    {
        $dumpPath = rtrim($backupDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'database.sql';
        if (!File::exists($dumpPath)) {
            return ['ok' => false, 'message' => 'Dump SQL introuvable.'];
        }

        $connection = (string) config('database.default', 'mysql');
        $driver = (string) config("database.connections.{$connection}.driver", 'mysql');

        if ($driver !== 'mysql') {
            return ['ok' => false, 'message' => 'Rollback DB auto supporte uniquement mysql.'];
        }

        $host = (string) config("database.connections.{$connection}.host", '127.0.0.1');
        $port = (string) config("database.connections.{$connection}.port", '3306');
        $database = (string) config("database.connections.{$connection}.database", '');
        $username = (string) config("database.connections.{$connection}.username", '');
        $password = (string) config("database.connections.{$connection}.password", '');

        if ($database === '' || $username === '') {
            return ['ok' => false, 'message' => 'Config DB incomplete pour rollback.'];
        }

        $command = [
            'mysql',
            '--host=' . $host,
            '--port=' . $port,
            '--user=' . $username,
        ];

        if ($password !== '') {
            $command[] = '--password=' . $password;
        }

        $command[] = $database;

        $process = new Process($command);
        $process->setInput((string) File::get($dumpPath));
        $process->setTimeout(300);
        $process->run();

        if (!$process->isSuccessful()) {
            return ['ok' => false, 'message' => 'Rollback DB echoue: mysql indisponible ou import KO.'];
        }

        return ['ok' => true, 'message' => 'Rollback DB execute avec succes.'];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function history(int $limit = 30): array
    {
        $path = storage_path(self::LOG_FILE);
        if (!File::exists($path)) {
            return [];
        }

        $lines = preg_split('/\r\n|\r|\n/', (string) File::get($path)) ?: [];
        $entries = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $decoded = json_decode($line, true);
            if (is_array($decoded)) {
                $entries[] = $decoded;
            }
        }

        return array_slice(array_reverse($entries), 0, $limit);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function log(string $event, array $payload = []): void
    {
        $path = storage_path(self::LOG_FILE);
        File::ensureDirectoryExists(dirname($path));

        $entry = [
            'timestamp' => now()->toIso8601String(),
            'event' => $event,
            'payload' => $payload,
        ];

        File::append($path, json_encode($entry, JSON_UNESCAPED_SLASHES) . PHP_EOL);
    }
}
