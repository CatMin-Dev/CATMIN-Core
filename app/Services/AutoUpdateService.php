<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use ZipArchive;

class AutoUpdateService
{
    private const LOG_FILE = 'logs/update-history.jsonl';
    private const ACTIVE_STATE_FILE = 'app/updates/active-update.json';

    /**
     * @return array<string, mixed>
     */
    public function status(): array
    {
        $runtime = app(AdminRuntimeInfoService::class)->get();

        return [
            'current_version' => (string) config('app.dashboard_version', 'V3-dev'),
            'revision' => (string) ($runtime['revision'] ?? 'n/a'),
            'branch' => (string) ($runtime['branch'] ?? 'n/a'),
            'last_update' => $this->lastUpdateEntry(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function downloadPackage(string $url, string $sha256): array
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return ['ok' => false, 'message' => 'URL de package invalide.'];
        }

        if (!preg_match('/^[a-f0-9]{64}$/i', $sha256)) {
            return ['ok' => false, 'message' => 'Checksum SHA-256 invalide.'];
        }

        $updateId = now()->format('Ymd-His') . '-' . Str::lower(Str::random(6));
        $updatesDir = storage_path('app/updates');
        File::ensureDirectoryExists($updatesDir);

        $archivePath = $updatesDir . '/' . $updateId . '.zip';

        try {
            $response = Http::timeout(120)->retry(2, 600)->get($url);
            if (!$response->successful()) {
                return ['ok' => false, 'message' => 'Téléchargement échoué: HTTP ' . $response->status()];
            }

            File::put($archivePath, $response->body());

            $actualHash = hash_file('sha256', $archivePath) ?: '';
            if (!hash_equals(strtolower($sha256), strtolower($actualHash))) {
                File::delete($archivePath);

                $this->log('download_failed', [
                    'reason' => 'checksum_mismatch',
                    'url' => $url,
                    'expected' => strtolower($sha256),
                    'actual' => strtolower($actualHash),
                ]);

                return ['ok' => false, 'message' => 'Checksum invalide: package rejeté pour sécurité.'];
            }

            $manifest = $this->readPackageManifest($archivePath);
            if (($manifest['ok'] ?? false) !== true) {
                File::delete($archivePath);
                return $manifest;
            }

            $payload = [
                'id' => $updateId,
                'archive_path' => $archivePath,
                'url' => $url,
                'sha256' => strtolower($actualHash),
                'target_version' => (string) ($manifest['manifest']['version'] ?? 'unknown'),
                'downloaded_at' => now()->toIso8601String(),
                'manifest' => $manifest['manifest'],
            ];

            File::put(storage_path(self::ACTIVE_STATE_FILE), json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            $this->log('download_ok', [
                'update_id' => $updateId,
                'target_version' => $payload['target_version'],
                'archive_path' => $archivePath,
            ]);

            return [
                'ok' => true,
                'message' => 'Package téléchargé et vérifié.',
                'update' => $payload,
            ];
        } catch (\Throwable $e) {
            File::delete($archivePath);
            return ['ok' => false, 'message' => 'Erreur téléchargement: ' . $e->getMessage()];
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function applyDownloadedUpdate(bool $withCoreMigrate = true): array
    {
        $active = $this->activeUpdate();
        if ($active === null) {
            return ['ok' => false, 'message' => 'Aucune update téléchargée en attente.'];
        }

        $updateId = (string) ($active['id'] ?? 'unknown');

        $backup = BackupService::create([
            'with_db' => true,
            'with_media' => true,
            'with_extensions' => true,
            'name' => 'pre-update-' . $updateId,
        ]);

        if (($backup['ok'] ?? false) !== true) {
            return ['ok' => false, 'message' => 'Backup pré-update impossible.'];
        }

        $previous = [
            'dashboard_version' => (string) config('app.dashboard_version', 'V3-dev'),
            'backup_dir' => (string) ($backup['backup_dir'] ?? ''),
        ];

        $this->rememberRollbackPoint($updateId, $previous);
        $this->log('apply_started', [
            'update_id' => $updateId,
            'backup_dir' => $previous['backup_dir'],
        ]);

        try {
            $this->extractAndSync((string) $active['archive_path']);
            $this->runMigrations($withCoreMigrate);

            $targetVersion = (string) ($active['target_version'] ?? '');
            if ($targetVersion !== '' && VersioningService::isDashboardVersionValid($targetVersion)) {
                ModuleVersionManager::setDashboardVersion($targetVersion);
            }

            $this->log('apply_success', [
                'update_id' => $updateId,
                'target_version' => $targetVersion,
            ]);

            File::delete(storage_path(self::ACTIVE_STATE_FILE));

            return [
                'ok' => true,
                'message' => 'Update appliquée avec succès.',
                'update_id' => $updateId,
                'backup_dir' => $previous['backup_dir'],
            ];
        } catch (\Throwable $e) {
            $rollback = $this->rollbackLast();
            $this->log('apply_failed', [
                'update_id' => $updateId,
                'error' => $e->getMessage(),
                'rollback_ok' => (bool) ($rollback['ok'] ?? false),
            ]);

            return [
                'ok' => false,
                'message' => 'Update échouée, rollback exécuté: ' . (($rollback['ok'] ?? false) ? 'OK' : 'ÉCHEC'),
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rollbackLast(): array
    {
        $last = $this->lastRollbackPoint();
        if ($last === null) {
            return ['ok' => false, 'message' => 'Aucun point de rollback disponible.'];
        }

        $backupDir = (string) ($last['backup_dir'] ?? '');
        if ($backupDir === '' || !File::exists($backupDir)) {
            return ['ok' => false, 'message' => 'Backup rollback introuvable.'];
        }

        try {
            $this->restoreFromBackup($backupDir);

            $prevVersion = (string) ($last['dashboard_version'] ?? '');
            if ($prevVersion !== '' && VersioningService::isDashboardVersionValid($prevVersion)) {
                ModuleVersionManager::setDashboardVersion($prevVersion);
            }

            $this->log('rollback_success', [
                'backup_dir' => $backupDir,
                'dashboard_version' => $prevVersion,
            ]);

            return ['ok' => true, 'message' => 'Rollback terminé.', 'backup_dir' => $backupDir];
        } catch (\Throwable $e) {
            $this->log('rollback_failed', ['error' => $e->getMessage()]);
            return ['ok' => false, 'message' => 'Rollback échoué: ' . $e->getMessage()];
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function history(int $limit = 20): array
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
     * @return array<string, mixed>|null
     */
    public function activeUpdate(): ?array
    {
        $path = storage_path(self::ACTIVE_STATE_FILE);
        if (!File::exists($path)) {
            return null;
        }

        $decoded = json_decode((string) File::get($path), true);

        return is_array($decoded) ? $decoded : null;
    }

    private function extractAndSync(string $archivePath): void
    {
        if (!File::exists($archivePath)) {
            throw new \RuntimeException('Archive update introuvable.');
        }

        $tmpDir = storage_path('app/updates/tmp-' . now()->format('Ymd-His') . '-' . Str::lower(Str::random(5)));
        File::ensureDirectoryExists($tmpDir);

        $zip = new ZipArchive();
        $openResult = $zip->open($archivePath);
        if ($openResult !== true) {
            throw new \RuntimeException('Impossible d’ouvrir l’archive update.');
        }

        $zip->extractTo($tmpDir);
        $zip->close();

        $root = $this->detectExtractRoot($tmpDir);

        $protected = [
            '.env',
            'storage',
            'runtime',
            'node_modules',
            'vendor',
            '.git',
        ];

        foreach (File::allFiles($root) as $file) {
            $source = $file->getPathname();
            $relative = ltrim(str_replace($root, '', $source), DIRECTORY_SEPARATOR);

            foreach ($protected as $prefix) {
                if ($relative === $prefix || str_starts_with($relative, $prefix . DIRECTORY_SEPARATOR)) {
                    continue 2;
                }
            }

            $target = base_path($relative);
            File::ensureDirectoryExists(dirname($target));
            File::copy($source, $target);
        }

        File::deleteDirectory($tmpDir);
    }

    private function runMigrations(bool $withCoreMigrate): void
    {
        if ($withCoreMigrate) {
            Artisan::call('migrate', ['--force' => true]);
        }

        $exit = Artisan::call('catmin:migrate:extensions');
        if ($exit !== 0) {
            throw new \RuntimeException('Migrations extensions échouées.');
        }

        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('view:clear');
        Artisan::call('route:clear');
    }

    private function restoreFromBackup(string $backupDir): void
    {
        $dbDump = $backupDir . '/database.sql';
        if (File::exists($dbDump)) {
            $this->restoreMysqlDump($dbDump);
        }

        $settingsExport = $backupDir . '/settings-export.json';
        if (File::exists($settingsExport)) {
            SettingsTransferService::importFromFile($settingsExport, true);
        }

        $mediaBackup = $backupDir . '/media';
        if (File::exists($mediaBackup)) {
            $mediaTarget = storage_path('app/public/media');
            File::deleteDirectory($mediaTarget);
            File::copyDirectory($mediaBackup, $mediaTarget);
        }

        $addonsBackup = $backupDir . '/addons';
        if (File::exists($addonsBackup)) {
            $addonsTarget = base_path((string) config('catmin.addons.path', 'addons'));
            File::deleteDirectory($addonsTarget);
            File::copyDirectory($addonsBackup, $addonsTarget);
        }
    }

    private function restoreMysqlDump(string $dumpPath): void
    {
        $connection = (string) config('database.default', 'mysql');
        $driver = (string) config("database.connections.{$connection}.driver", 'mysql');

        if ($driver !== 'mysql') {
            return;
        }

        $host = (string) config("database.connections.{$connection}.host", '127.0.0.1');
        $port = (string) config("database.connections.{$connection}.port", '3306');
        $database = (string) config("database.connections.{$connection}.database", '');
        $username = (string) config("database.connections.{$connection}.username", '');
        $password = (string) config("database.connections.{$connection}.password", '');

        if ($database === '' || $username === '') {
            return;
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
        $process->setTimeout(180);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException('Restore DB impossible: mysql CLI indisponible ou erreur d\'import.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function readPackageManifest(string $archivePath): array
    {
        $zip = new ZipArchive();
        $openResult = $zip->open($archivePath);

        if ($openResult !== true) {
            return ['ok' => false, 'message' => 'Archive invalide.'];
        }

        $manifest = null;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = (string) $zip->getNameIndex($i);
            if (str_ends_with($name, 'update-manifest.json')) {
                $manifest = $zip->getFromIndex($i);
                break;
            }
        }

        $zip->close();

        if (!is_string($manifest) || trim($manifest) === '') {
            return ['ok' => false, 'message' => 'update-manifest.json manquant dans le package.'];
        }

        $decoded = json_decode($manifest, true);
        if (!is_array($decoded)) {
            return ['ok' => false, 'message' => 'update-manifest.json invalide.'];
        }

        if (empty($decoded['version']) || !is_string($decoded['version'])) {
            return ['ok' => false, 'message' => 'Version cible manquante dans le manifest.'];
        }

        return ['ok' => true, 'manifest' => $decoded];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function lastUpdateEntry(): ?array
    {
        $history = $this->history(1);

        return $history[0] ?? null;
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

    /**
     * @param array<string, mixed> $payload
     */
    private function rememberRollbackPoint(string $updateId, array $payload): void
    {
        $path = storage_path('app/updates/rollback-' . $updateId . '.json');
        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /**
     * @return array<string, mixed>|null
     */
    private function lastRollbackPoint(): ?array
    {
        $files = File::glob(storage_path('app/updates/rollback-*.json'));
        if (empty($files)) {
            return null;
        }

        sort($files);
        $latest = end($files);
        if (!is_string($latest)) {
            return null;
        }

        $decoded = json_decode((string) File::get($latest), true);

        return is_array($decoded) ? $decoded : null;
    }

    private function detectExtractRoot(string $tmpDir): string
    {
        $entries = File::directories($tmpDir);
        $files = File::files($tmpDir);

        if (count($entries) === 1 && count($files) === 0) {
            return $entries[0];
        }

        return $tmpDir;
    }
}
