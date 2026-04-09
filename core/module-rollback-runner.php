<?php

declare(strict_types=1);

require_once CATMIN_CORE . '/module-snapshot-storage.php';
require_once CATMIN_CORE . '/module-snapshot-logger.php';
require_once CATMIN_CORE . '/events-bus.php';

final class CoreModuleRollbackRunner
{
    public function __construct(
        private readonly CoreModuleSnapshotStorage $storage = new CoreModuleSnapshotStorage(),
        private readonly CoreModuleSnapshotLogger $logger = new CoreModuleSnapshotLogger(),
    ) {}

    /** @return array{ok:bool,message:string} */
    public function rollback(string $slug, string $snapshotId): array
    {
        $slug = strtolower(trim($slug));
        $snapshotId = trim($snapshotId);
        catmin_event_emit('module.rollback.requested', [
            'slug' => $slug,
            'snapshot_id' => $snapshotId,
        ]);
        if ($slug === '' || $snapshotId === '') {
            return ['ok' => false, 'message' => 'Snapshot invalide'];
        }

        $metaPath = $this->storage->metadataPath($slug, $snapshotId);
        $raw = @file_get_contents($metaPath);
        $snapshot = is_string($raw) ? json_decode($raw, true) : null;
        if (!is_array($snapshot)) {
            return ['ok' => false, 'message' => 'Snapshot introuvable'];
        }

        $scope = strtolower(trim((string) ($snapshot['scope'] ?? '')));
        $targetPath = CATMIN_MODULES . '/' . $scope . '/' . $slug;
        $filesPath = (string) ($snapshot['files_path'] ?? '');
        if ($scope === '' || !is_dir($filesPath)) {
            return ['ok' => false, 'message' => 'Snapshot corrompu'];
        }

        $this->cleanupPath($targetPath);
        if (!@mkdir($targetPath, 0775, true) && !is_dir($targetPath)) {
            return ['ok' => false, 'message' => 'Préparation rollback impossible'];
        }
        if (!$this->copyDir($filesPath, $targetPath)) {
            return ['ok' => false, 'message' => 'Restauration fichiers impossible'];
        }

        $this->logger->log('snapshot.rollback', ['slug' => $slug, 'snapshot_id' => $snapshotId, 'scope' => $scope]);
        catmin_event_emit('module.rollback.completed', [
            'slug' => $slug,
            'scope' => $scope,
            'snapshot_id' => $snapshotId,
        ]);
        return ['ok' => true, 'message' => 'Rollback effectué depuis snapshot ' . $snapshotId];
    }

    private function copyDir(string $source, string $dest): bool
    {
        if (!is_dir($source)) {
            return false;
        }
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($it as $item) {
            $sourcePath = $item->getPathname();
            $relative = ltrim(str_replace(str_replace('\\', '/', $source), '', str_replace('\\', '/', $sourcePath)), '/');
            $targetPath = $dest . '/' . $relative;
            if ($item->isDir()) {
                if (!is_dir($targetPath) && !@mkdir($targetPath, 0775, true) && !is_dir($targetPath)) {
                    return false;
                }
                continue;
            }
            if (!@copy($sourcePath, $targetPath)) {
                return false;
            }
        }
        return true;
    }

    private function cleanupPath(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($it as $entry) {
            if ($entry->isDir()) {
                @rmdir($entry->getPathname());
            } else {
                @unlink($entry->getPathname());
            }
        }
        @rmdir($path);
    }
}
