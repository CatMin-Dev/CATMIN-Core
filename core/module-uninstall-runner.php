<?php

declare(strict_types=1);

require_once CATMIN_CORE . '/module-activator.php';
require_once CATMIN_CORE . '/module-uninstall-logger.php';
require_once CATMIN_CORE . '/module-snapshot-manager.php';
require_once CATMIN_CORE . '/module-state-store.php';
require_once CATMIN_CORE . '/module-migration-runner.php';
require_once CATMIN_CORE . '/module-data-retention-policy.php';

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

        $retentionPolicy = new CoreModuleDataRetentionPolicy();
        $normalizedPolicy = $retentionPolicy->normalize($policy);
        $destructive = $retentionPolicy->isDestructive($normalizedPolicy);

        $modulePath = CATMIN_MODULES . '/' . $scope . '/' . $slug;
        if (!is_dir($modulePath)) {
            return ['ok' => false, 'message' => 'Module introuvable'];
        }

        $snapshot = (new CoreModuleSnapshotManager())->create($scope, $slug, 'pre-uninstall', 'safe-uninstall');
        if (!(bool) ($snapshot['ok'] ?? false)) {
            return ['ok' => false, 'message' => 'Snapshot pré-uninstall impossible'];
        }

        $this->logger->log('uninstall.request', [
            'scope' => $scope,
            'slug' => $slug,
            'policy' => $normalizedPolicy,
            'destructive' => $destructive,
        ]);

        if ((bool) ($impact['enabled'] ?? false)) {
            $deactivate = (new CoreModuleActivator())->deactivate($scope, $slug);
            if (!(bool) ($deactivate['ok'] ?? false)) {
                $this->logger->log('uninstall.refused.deactivate', ['scope' => $scope, 'slug' => $slug, 'message' => (string) ($deactivate['message'] ?? '')]);
                return ['ok' => false, 'message' => (string) ($deactivate['message'] ?? 'Désactivation impossible')];
            }
        }

        $archivePath = '';
        if (!$destructive) {
            $archiveBase = CATMIN_STORAGE . '/modules/uninstall-archives';
            if (!is_dir($archiveBase)) {
                @mkdir($archiveBase, 0775, true);
            }
            $archivePath = $archiveBase . '/' . $slug . '-' . gmdate('YmdHis');
            @mkdir($archivePath, 0775, true);
            $this->copyDir($modulePath, $archivePath);
        }

        $lastMigration = '';
        if ($destructive) {
            $down = (new CoreModuleMigrationRunner())->run($modulePath, 'down', true);
            if (!(bool) ($down['ok'] ?? false)) {
                $this->logger->log('uninstall.refused.down_migrations', [
                    'scope' => $scope,
                    'slug' => $slug,
                    'message' => (string) ($down['message'] ?? ''),
                ]);

                return ['ok' => false, 'message' => (string) ($down['message'] ?? 'Migrations DOWN impossibles')];
            }

            $executed = (array) ($down['executed'] ?? []);
            if ($executed !== []) {
                $lastMigration = (string) end($executed);
            }
        }

        $this->cleanupPath($modulePath);
        (new CoreModuleStateStore())->markLifecycle(
            $slug,
            $destructive ? 'uninstalled_drop_data' : 'uninstalled_keep_data',
            $lastMigration
        );

        $this->logger->log('uninstall.success', [
            'scope' => $scope,
            'slug' => $slug,
            'policy' => $normalizedPolicy,
            'db_state' => $destructive ? 'uninstalled_drop_data' : 'uninstalled_keep_data',
            'archive_path' => $archivePath,
        ]);

        return [
            'ok' => true,
            'message' => 'Module désinstallé',
            'archive_path' => $archivePath,
            'snapshot_id' => (string) (($snapshot['snapshot']['snapshot_id'] ?? '')),
        ];
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

