<?php

declare(strict_types=1);

require_once CATMIN_CORE . '/module-staging-manager.php';
require_once CATMIN_CORE . '/module-zip-validator.php';
require_once CATMIN_CORE . '/module-collision-checker.php';
require_once CATMIN_CORE . '/module-compatibility-checker.php';
require_once CATMIN_CORE . '/module-install-rollback.php';
require_once CATMIN_CORE . '/module-install-logger.php';
require_once CATMIN_CORE . '/module-migration-runner.php';
require_once CATMIN_CORE . '/module-state-store.php';
require_once CATMIN_CORE . '/module-activation-guard.php';
require_once CATMIN_CORE . '/module-integrity.php';
require_once CATMIN_CORE . '/module-activator.php';
require_once CATMIN_CORE . '/module-snapshot-manager.php';
require_once CATMIN_CORE . '/module-loader.php';

final class CoreModuleInstallRunner
{
    public function installZip(string $zipPath, bool $activate = true, array $context = []): array
    {
        $staging = new CoreModuleStagingManager();
        $staging->ensure();
        $logger = new CoreModuleInstallLogger();
        $rollback = new CoreModuleInstallRollback();
        require_once CATMIN_CORE . '/events-bus.php';

        $logger->log('zip_received', 'ok', ['zip' => basename($zipPath)]);
        catmin_event_emit('module.install.started', [
            'zip' => basename($zipPath),
            'activate' => $activate,
            'context' => $context,
        ]);
        $zipValidation = (new CoreModuleZipValidator())->validateArchive($zipPath);
        if (!(bool) ($zipValidation['ok'] ?? false)) {
            $logger->log('zip_validate', 'error', ['errors' => $zipValidation['errors'] ?? []]);
            catmin_event_emit('module.install.failed', [
                'zip' => basename($zipPath),
                'errors' => (array) ($zipValidation['errors'] ?? []),
                'context' => $context,
            ]);
            return ['ok' => false, 'message' => 'ZIP module invalide', 'errors' => (array) ($zipValidation['errors'] ?? [])];
        }

        $runId = gmdate('Ymd-His') . '-' . bin2hex(random_bytes(4));
        $extractDir = $staging->stagingDir() . '/install-' . $runId;
        if (!is_dir($extractDir) && !@mkdir($extractDir, 0775, true) && !is_dir($extractDir)) {
            catmin_event_emit('module.install.failed', ['zip' => basename($zipPath), 'errors' => ['staging_create_failed'], 'context' => $context]);
            return ['ok' => false, 'message' => 'Creation dossier staging impossible', 'errors' => ['staging_create_failed']];
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true || !$zip->extractTo($extractDir)) {
            if ($zip->status !== ZipArchive::ER_OK) {
                $logger->log('zip_extract', 'error', ['status' => $zip->status]);
            }
            $zip->close();
            $rollback->cleanupPath($extractDir);
            catmin_event_emit('module.install.failed', ['zip' => basename($zipPath), 'errors' => ['zip_extract_failed'], 'context' => $context]);
            return ['ok' => false, 'message' => 'Extraction ZIP impossible', 'errors' => ['zip_extract_failed']];
        }
        $zip->close();

        $moduleRoot = $this->findManifestRoot($extractDir);
        if ($moduleRoot === null) {
            $rollback->cleanupPath($extractDir);
            catmin_event_emit('module.install.failed', ['zip' => basename($zipPath), 'errors' => ['manifest_missing'], 'context' => $context]);
            return ['ok' => false, 'message' => 'Manifest introuvable apres extraction', 'errors' => ['manifest_missing']];
        }

        $manifestResult = (new CoreModuleZipValidator())->readManifestFromExtracted($moduleRoot);
        if (!(bool) ($manifestResult['ok'] ?? false)) {
            $rollback->cleanupPath($extractDir);
            catmin_event_emit('module.install.failed', ['zip' => basename($zipPath), 'errors' => (array) ($manifestResult['errors'] ?? []), 'context' => $context]);
            return ['ok' => false, 'message' => 'Manifest module invalide', 'errors' => (array) ($manifestResult['errors'] ?? [])];
        }
        $manifest = (array) ($manifestResult['manifest'] ?? []);

        $compat = (new CoreModuleCompatibilityChecker())->check($manifest);
        if (!(bool) ($compat['compatible'] ?? false)) {
            $rollback->cleanupPath($extractDir);
            catmin_event_emit('module.install.failed', ['slug' => (string) ($manifest['slug'] ?? ''), 'errors' => (array) ($compat['errors'] ?? []), 'context' => $context]);
            return ['ok' => false, 'message' => 'Compatibilite module KO', 'errors' => (array) ($compat['errors'] ?? [])];
        }

        $collisions = (new CoreModuleCollisionChecker())->check($manifest);
        if (!(bool) ($collisions['ok'] ?? false)) {
            $rollback->cleanupPath($extractDir);
            catmin_event_emit('module.install.failed', ['slug' => (string) ($manifest['slug'] ?? ''), 'errors' => (array) ($collisions['errors'] ?? []), 'context' => $context]);
            return ['ok' => false, 'message' => 'Collision module detectee', 'errors' => (array) ($collisions['errors'] ?? [])];
        }

        $integrity = (new CoreModuleIntegrity())->verify($moduleRoot, $manifest);
        if (!((bool) ($integrity['trust']['trusted'] ?? false))) {
            $rollback->cleanupPath($extractDir);
            catmin_event_emit('module.install.failed', ['slug' => (string) ($manifest['slug'] ?? ''), 'errors' => (array) ($integrity['trust']['errors'] ?? []), 'context' => $context]);
            return ['ok' => false, 'message' => 'Confiance module insuffisante', 'errors' => (array) ($integrity['trust']['errors'] ?? [])];
        }

        $type = strtolower(trim((string) ($manifest['type'] ?? '')));
        $slug = strtolower(trim((string) ($manifest['slug'] ?? '')));

        if ($slug === 'cat-seo-meta' && !$this->isModuleActive('cat-slug')) {
            $rollback->cleanupPath($extractDir);
            catmin_event_emit('module.install.failed', ['slug' => $slug, 'errors' => ['dependency_required_active:cat-slug'], 'context' => $context]);
            return ['ok' => false, 'message' => 'CAT SEO Meta requiert CAT Slug actif avant installation', 'errors' => ['dependency_required_active:cat-slug']];
        }

        $dest = CATMIN_MODULES . '/' . $type . '/' . $slug;
        $snapshotManager = new CoreModuleSnapshotManager();
        if (is_dir($dest)) {
            $snapshotManager->create($type, $slug, 'pre-update', 'installer');
        }
        if (!is_dir(dirname($dest)) && !@mkdir(dirname($dest), 0775, true) && !is_dir(dirname($dest))) {
            $rollback->cleanupPath($extractDir);
            catmin_event_emit('module.install.failed', ['slug' => $slug, 'errors' => ['destination_prepare_failed'], 'context' => $context]);
            return ['ok' => false, 'message' => 'Preparation destination impossible', 'errors' => ['destination_prepare_failed']];
        }
        if (!@rename($moduleRoot, $dest)) {
            if (!$this->copyDir($moduleRoot, $dest)) {
                $rollback->cleanupPath($extractDir);
                catmin_event_emit('module.install.failed', ['slug' => $slug, 'errors' => ['move_failed'], 'context' => $context]);
                return ['ok' => false, 'message' => 'Deplacement final module impossible', 'errors' => ['move_failed']];
            }
        }
        $rollback->cleanupPath($extractDir);

        $migrations = (new CoreModuleMigrationRunner())->run($dest);
        $stateStore = new CoreModuleStateStore();
        $stateStore->persist($slug, (string) ($manifest['name'] ?? $slug), (string) ($manifest['version'] ?? '0.0.0'), false);

        if ($activate) {
            $guard = (new CoreModuleActivationGuard())->assertCanActivate($dest, $manifest, (string) ($context['repo_trust'] ?? ''));
            if (!(bool) ($guard['ok'] ?? false)) {
                catmin_event_emit('module.install.failed', ['slug' => $slug, 'errors' => (array) ($guard['errors'] ?? []), 'context' => $context]);
                return ['ok' => false, 'message' => 'Installation OK mais activation refusee', 'errors' => (array) ($guard['errors'] ?? [])];
            }
            $activation = (new CoreModuleActivator())->activate($type, $slug);
            if (!(bool) ($activation['ok'] ?? false)) {
                catmin_event_emit('module.install.failed', ['slug' => $slug, 'errors' => [(string) ($activation['message'] ?? 'activation_failed')], 'context' => $context]);
                return ['ok' => false, 'message' => 'Installation OK mais activation KO', 'errors' => [(string) ($activation['message'] ?? 'activation_failed')]];
            }
        }

        $logger->log('install_complete', 'ok', ['slug' => $slug, 'type' => $type, 'activated' => $activate]);
        catmin_event_emit('module.installed', [
            'scope' => $type,
            'slug' => $slug,
            'version' => (string) ($manifest['version'] ?? '0.0.0'),
            'activated' => $activate,
            'context' => $context,
        ]);
        return [
            'ok' => true,
            'message' => 'Module installe avec succes',
            'errors' => [],
            'manifest' => $manifest,
            'migrations' => $migrations,
            'integrity' => $integrity,
        ];
    }

    private function findManifestRoot(string $extractedRoot): ?string
    {
        if (is_file($extractedRoot . '/manifest.json')) {
            return $extractedRoot;
        }
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($extractedRoot, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($it as $item) {
            if (!$item->isDir()) {
                continue;
            }
            $candidate = $item->getPathname() . '/manifest.json';
            if (is_file($candidate)) {
                return $item->getPathname();
            }
        }
        return null;
    }

    private function copyDir(string $source, string $dest): bool
    {
        if (!is_dir($source)) {
            return false;
        }
        if (!is_dir($dest) && !@mkdir($dest, 0775, true) && !is_dir($dest)) {
            return false;
        }
        $items = scandir($source);
        if (!is_array($items)) {
            return false;
        }
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $src = $source . '/' . $item;
            $dst = $dest . '/' . $item;
            if (is_dir($src)) {
                if (!$this->copyDir($src, $dst)) {
                    return false;
                }
                continue;
            }
            if (!@copy($src, $dst)) {
                return false;
            }
        }
        return true;
    }

    private function isModuleActive(string $slug): bool
    {
        $slug = strtolower(trim($slug));
        if ($slug === '') {
            return false;
        }

        $snapshot = (new CoreModuleLoader())->scan();
        foreach ((array) ($snapshot['modules'] ?? []) as $module) {
            $mSlug = strtolower(trim((string) ($module['manifest']['slug'] ?? '')));
            if ($mSlug === $slug) {
                return (bool) ($module['enabled'] ?? false);
            }
        }

        return false;
    }
}
