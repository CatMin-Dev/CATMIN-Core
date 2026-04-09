<?php

declare(strict_types=1);

require_once CATMIN_CORE . '/module-loader.php';
require_once CATMIN_CORE . '/module-snapshot-storage.php';
require_once CATMIN_CORE . '/module-snapshot-logger.php';

final class CoreModuleSnapshotManager
{
    public function __construct(
        private readonly CoreModuleSnapshotStorage $storage = new CoreModuleSnapshotStorage(),
        private readonly CoreModuleSnapshotLogger $logger = new CoreModuleSnapshotLogger(),
    ) {}

    /** @return array{ok:bool,message:string,snapshot?:array<string,mixed>} */
    public function create(string $scope, string $slug, string $type = 'emergency', string $reason = ''): array
    {
        $scope = strtolower(trim($scope));
        $slug = strtolower(trim($slug));
        if ($scope === '' || $slug === '') {
            return ['ok' => false, 'message' => 'Module invalide'];
        }

        $modulePath = CATMIN_MODULES . '/' . $scope . '/' . $slug;
        if (!is_dir($modulePath)) {
            return ['ok' => false, 'message' => 'Module introuvable'];
        }

        $scan = (new CoreModuleLoader())->scan();
        $moduleRow = null;
        foreach ((array) ($scan['modules'] ?? []) as $module) {
            $m = is_array($module['manifest'] ?? null) ? $module['manifest'] : [];
            if (strtolower((string) ($m['slug'] ?? '')) === $slug && strtolower((string) ($m['type'] ?? '')) === $scope) {
                $moduleRow = $module;
                break;
            }
        }
        if (!is_array($moduleRow)) {
            return ['ok' => false, 'message' => 'Module non détecté par loader'];
        }

        $this->storage->ensure();
        $snapshotId = gmdate('YmdHis') . '-' . bin2hex(random_bytes(3));
        $target = $this->storage->filesPath($slug, $snapshotId);
        if (!@mkdir($target, 0775, true) && !is_dir($target)) {
            return ['ok' => false, 'message' => 'Création snapshot impossible'];
        }
        if (!$this->copyDir($modulePath, $target)) {
            return ['ok' => false, 'message' => 'Copie snapshot impossible'];
        }

        $manifest = is_array($moduleRow['manifest'] ?? null) ? $moduleRow['manifest'] : [];
        $snapshot = [
            'snapshot_id' => $snapshotId,
            'type' => $type,
            'reason' => $reason,
            'created_at' => gmdate('c'),
            'scope' => $scope,
            'slug' => $slug,
            'module_path' => $modulePath,
            'files_path' => $target,
            'version' => (string) ($manifest['version'] ?? '0.0.0'),
            'enabled' => (bool) ($moduleRow['enabled'] ?? false),
            'release_channel' => (string) ($manifest['release_channel'] ?? 'stable'),
            'lifecycle_status' => (string) ($manifest['lifecycle_status'] ?? 'active'),
        ];

        $ok = @file_put_contents(
            $this->storage->metadataPath($slug, $snapshotId),
            (string) json_encode($snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL
        );
        if ($ok === false) {
            return ['ok' => false, 'message' => 'Écriture metadata snapshot impossible'];
        }

        $this->enforceRetention($slug, 6);
        $this->logger->log('snapshot.created', ['scope' => $scope, 'slug' => $slug, 'snapshot_id' => $snapshotId, 'type' => $type]);

        return ['ok' => true, 'message' => 'Snapshot créé', 'snapshot' => $snapshot];
    }

    /** @return array<int,array<string,mixed>> */
    public function list(string $slug): array
    {
        return $this->storage->listBySlug($slug);
    }

    private function enforceRetention(string $slug, int $max): void
    {
        $rows = $this->storage->listBySlug($slug);
        if (count($rows) <= $max) {
            return;
        }
        $toDelete = array_slice($rows, $max);
        foreach ($toDelete as $row) {
            $files = (string) ($row['files_path'] ?? '');
            $meta = $this->storage->metadataPath($slug, (string) ($row['snapshot_id'] ?? ''));
            $this->cleanupPath($files);
            if (is_file($meta)) {
                @unlink($meta);
            }
        }
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
        if ($path === '' || !is_dir($path)) {
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

