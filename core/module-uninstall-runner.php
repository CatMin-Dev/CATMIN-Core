<?php

declare(strict_types=1);

use Core\database\ConnectionManager;

require_once CATMIN_CORE . '/module-activator.php';
require_once CATMIN_CORE . '/module-uninstall-logger.php';
require_once CATMIN_CORE . '/module-snapshot-manager.php';

final class CoreModuleUninstallRunner
{
    public function __construct(
        private readonly CoreModuleUninstallLogger $logger = new CoreModuleUninstallLogger(),
    ) {}

    /** @param array<string,mixed> $impact */
    public function run(array $impact, string $policy): array
    {
        $scope = strtolower(trim((string) ($impact['scope'] ?? '')));
        $slug = strtolower(trim((string) ($impact['slug'] ?? '')));
        if ($scope === '' || $slug === '') {
            return ['ok' => false, 'message' => 'Module invalide'];
        }

        $modulePath = CATMIN_MODULES . '/' . $scope . '/' . $slug;
        if (!is_dir($modulePath)) {
            return ['ok' => false, 'message' => 'Module introuvable'];
        }

        $snapshot = (new CoreModuleSnapshotManager())->create($scope, $slug, 'pre-uninstall', 'safe-uninstall');
        if (!(bool) ($snapshot['ok'] ?? false)) {
            return ['ok' => false, 'message' => 'Snapshot pré-uninstall impossible'];
        }

        $this->logger->log('uninstall.request', ['scope' => $scope, 'slug' => $slug, 'policy' => $policy]);

        if ((bool) ($impact['enabled'] ?? false)) {
            $deactivate = (new CoreModuleActivator())->deactivate($scope, $slug);
            if (!(bool) ($deactivate['ok'] ?? false)) {
                $this->logger->log('uninstall.refused.deactivate', ['scope' => $scope, 'slug' => $slug, 'message' => (string) ($deactivate['message'] ?? '')]);
                return ['ok' => false, 'message' => (string) ($deactivate['message'] ?? 'Désactivation impossible')];
            }
        }

        $archivePath = '';
        if ($policy === 'archive_data') {
            $archiveBase = CATMIN_STORAGE . '/modules/uninstall-archives';
            if (!is_dir($archiveBase)) {
                @mkdir($archiveBase, 0775, true);
            }
            $archivePath = $archiveBase . '/' . $slug . '-' . gmdate('YmdHis');
            @mkdir($archivePath, 0775, true);
            $this->copyDir($modulePath, $archivePath);
        }

        $this->cleanupPath($modulePath);
        $this->removeModuleState($slug);

        $this->logger->log('uninstall.success', [
            'scope' => $scope,
            'slug' => $slug,
            'policy' => $policy,
            'archive_path' => $archivePath,
        ]);

        return [
            'ok' => true,
            'message' => 'Module désinstallé',
            'archive_path' => $archivePath,
            'snapshot_id' => (string) (($snapshot['snapshot']['snapshot_id'] ?? '')),
        ];
    }

    private function removeModuleState(string $slug): void
    {
        try {
            $table = (string) config('database.prefixes.core', 'core_') . 'modules';
            $pdo = (new ConnectionManager())->connection();
            $stmt = $pdo->prepare('DELETE FROM ' . $table . ' WHERE slug = :slug');
            $stmt->execute(['slug' => $slug]);
        } catch (\Throwable $e) {
            $this->logger->log('uninstall.state.cleanup_failed', ['slug' => $slug, 'error' => $e->getMessage()]);
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

