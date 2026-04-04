<?php

namespace Addons\CatminBackupS3\Services;

use Illuminate\Support\Facades\File;
use Modules\Logger\Services\AlertingService;
use Modules\Logger\Services\SystemLogService;
use ZipArchive;

class RemoteBackupService
{
    public function __construct(
        private readonly RemoteBackupSettingsService $settingsService,
        private readonly RemoteStorageProviderFactory $providerFactory,
    ) {
    }

    /** @return array<int, array<string, mixed>> */
    public function listLocalBackups(): array
    {
        $base = storage_path('app/backups');
        if (!is_dir($base)) {
            return [];
        }

        $rows = [];
        foreach ((array) File::directories($base) as $dir) {
            $name = basename($dir);
            $manifest = $dir . '/manifest.json';
            $meta = [];

            if (File::exists($manifest)) {
                $decoded = json_decode((string) File::get($manifest), true);
                $meta = is_array($decoded) ? $decoded : [];
            }

            $rows[] = [
                'name' => $name,
                'path' => $dir,
                'size' => $this->directorySize($dir),
                'created_at' => (string) ($meta['created_at'] ?? date('c', filemtime($dir) ?: time())),
                'format' => (string) ($meta['format'] ?? 'unknown'),
            ];
        }

        usort($rows, static fn (array $a, array $b) => strcmp((string) $b['created_at'], (string) $a['created_at']));

        return $rows;
    }

    /** @return array<int, array{path:string,size:int,last_modified:int,type:string,source:string}> */
    public function listRemoteBackups(): array
    {
        $settings = $this->settingsService->all(true);
        $client = $this->providerFactory->make($settings);
        $prefix = trim((string) ($settings['prefix'] ?? ''), '/');

        return $client->list($prefix);
    }

    /**
     * @return array{ok:bool,message:string,remote_path?:string}
     */
    public function uploadLocalBackup(string $localBackupName): array
    {
        $settings = $this->settingsService->all(true);
        $client = $this->providerFactory->make($settings);

        $dir = storage_path('app/backups/' . $localBackupName);
        if (!is_dir($dir)) {
            return ['ok' => false, 'message' => 'Backup local introuvable.'];
        }

        $zipPath = $this->buildArchive($dir, $localBackupName);
        $prefix = trim((string) ($settings['prefix'] ?? 'catmin/backups'), '/');
        $remotePath = $prefix . '/' . basename($zipPath);

        try {
            $client->upload($zipPath, $remotePath);
            @unlink($zipPath);

            $this->logSuccess('backup.remote.upload.ok', 'Upload backup distant reussi', [
                'provider' => (string) ($settings['provider'] ?? ''),
                'backup' => $localBackupName,
                'remote_path' => $remotePath,
            ]);

            return ['ok' => true, 'message' => 'Backup distant uploadé.', 'remote_path' => $remotePath];
        } catch (\Throwable $e) {
            $this->logError('backup.remote.upload.failed', 'Upload backup distant echoue', $e, [
                'provider' => (string) ($settings['provider'] ?? ''),
                'backup' => $localBackupName,
            ]);

            return ['ok' => false, 'message' => 'Upload remote echoue: ' . $e->getMessage()];
        }
    }

    /**
     * @return array{ok:bool,message:string,local_path?:string}
     */
    public function downloadToLocal(string $remotePath): array
    {
        $settings = $this->settingsService->all(true);
        $client = $this->providerFactory->make($settings);

        $targetDir = storage_path('app/backups/remote-downloads');
        File::ensureDirectoryExists($targetDir);
        $filename = basename($remotePath);
        $localPath = $targetDir . '/' . $filename;

        try {
            $client->download($remotePath, $localPath);

            $this->logSuccess('backup.remote.download.ok', 'Download backup distant reussi', [
                'provider' => (string) ($settings['provider'] ?? ''),
                'remote_path' => $remotePath,
                'local_path' => $localPath,
            ]);

            return [
                'ok' => true,
                'message' => 'Backup distant telecharge. Restore a preparer depuis ' . $localPath,
                'local_path' => $localPath,
            ];
        } catch (\Throwable $e) {
            $this->logError('backup.remote.download.failed', 'Download backup distant echoue', $e, [
                'provider' => (string) ($settings['provider'] ?? ''),
                'remote_path' => $remotePath,
            ]);

            return ['ok' => false, 'message' => 'Download remote echoue: ' . $e->getMessage()];
        }
    }

    /** @return array{ok:bool,message:string,deleted:int} */
    public function applyRetention(): array
    {
        $settings = $this->settingsService->all(true);
        $client = $this->providerFactory->make($settings);

        $max = max(1, (int) ($settings['retention_max'] ?? 15));
        $prefix = trim((string) ($settings['prefix'] ?? ''), '/');
        $files = $client->list($prefix);

        if (count($files) <= $max) {
            return ['ok' => true, 'message' => 'Retention OK, aucune purge necessaire.', 'deleted' => 0];
        }

        usort($files, static fn (array $a, array $b) => ($a['last_modified'] <=> $b['last_modified']));
        $toDelete = array_slice($files, 0, count($files) - $max);

        $deleted = 0;
        foreach ($toDelete as $row) {
            try {
                $client->delete((string) $row['path']);
                $deleted++;
            } catch (\Throwable $e) {
                $this->logError('backup.remote.retention.delete_failed', 'Echec purge retention', $e, [
                    'path' => (string) $row['path'],
                    'provider' => (string) ($settings['provider'] ?? ''),
                ]);
            }
        }

        $this->logSuccess('backup.remote.retention.ok', 'Retention backup distante executee', [
            'provider' => (string) ($settings['provider'] ?? ''),
            'deleted' => $deleted,
            'max' => $max,
        ]);

        return ['ok' => true, 'message' => 'Retention appliquee.', 'deleted' => $deleted];
    }

    public function testConnection(): bool
    {
        $settings = $this->settingsService->all(true);
        $client = $this->providerFactory->make($settings);

        return $client->testConnection();
    }

    private function buildArchive(string $dir, string $name): string
    {
        $tmpDir = storage_path('app/remote-backups/tmp');
        File::ensureDirectoryExists($tmpDir);

        $zipPath = $tmpDir . '/' . $name . '-' . now()->format('Ymd-His') . '.zip';
        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Impossible de creer l\'archive zip.');
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($files as $file) {
            $path = (string) $file->getPathname();
            $relative = ltrim(str_replace($dir, '', $path), DIRECTORY_SEPARATOR);

            if ($relative === '') {
                continue;
            }

            if ($file->isDir()) {
                $zip->addEmptyDir($relative);
            } else {
                $zip->addFile($path, $relative);
            }
        }

        $zip->close();

        return $zipPath;
    }

    private function directorySize(string $dir): int
    {
        $size = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            $size += (int) $file->getSize();
        }

        return $size;
    }

    /** @param array<string,mixed> $context */
    private function logSuccess(string $event, string $message, array $context): void
    {
        try {
            app(SystemLogService::class)->logAudit($event, $message, $context, 'info', (string) session('catmin_admin_username', 'system'));
        } catch (\Throwable) {
        }
    }

    /** @param array<string,mixed> $context */
    private function logError(string $event, string $message, \Throwable $e, array $context): void
    {
        try {
            app(SystemLogService::class)->logAudit($event, $message, array_merge($context, ['error' => $e->getMessage()]), 'error', (string) session('catmin_admin_username', 'system'));
        } catch (\Throwable) {
        }

        try {
            app(AlertingService::class)->createAlert(
                'critical_error',
                'Remote backup error',
                $message . ': ' . $e->getMessage(),
                $context,
                'warning'
            );
        } catch (\Throwable) {
        }
    }
}
