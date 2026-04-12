<?php

declare(strict_types=1);

require_once CATMIN_CORE . '/module-loader.php';
require_once CATMIN_CORE . '/module-activation-guard.php';
require_once CATMIN_CORE . '/module-migration-runner.php';
require_once CATMIN_CORE . '/module-state-store.php';
require_once CATMIN_CORE . '/module-snapshot-manager.php';

final class CoreModuleUpdater
{
    /**
     * Run a complete in-place update of an already-installed module:
     *  1. Re-verify checksums + signature (integrity guard)
     *  2. Create a pre-update snapshot
     *  3. Re-run migrations (migrations MUST be idempotent)
     *  4. Sync the DB version record (preserves current active/inactive state)
     *
     * @return array{ok:bool, message:string, scope:string, slug:string, manifest_version:string, db_version_before:string, integrity:array, migrations:array, errors:array<int,string>}
     */
    public function update(string $scope, string $slug): array
    {
        $scope = strtolower(trim($scope));
        $slug  = strtolower(trim($slug));

        if ($scope === '' || $slug === '') {
            return $this->fail('Paramètres module invalides', $scope, $slug);
        }

        $path         = CATMIN_MODULES . '/' . $scope . '/' . $slug;
        $manifestPath = is_file($path . '/manifest.json') ? ($path . '/manifest.json') : ($path . '/module.json');

        if (!is_file($manifestPath)) {
            return $this->fail('Manifest introuvable pour ' . $scope . '/' . $slug, $scope, $slug);
        }

        $raw      = file_get_contents($manifestPath);
        $manifest = is_string($raw) ? json_decode($raw, true) : null;
        if (!is_array($manifest)) {
            return $this->fail('Manifest invalide (JSON mal formé)', $scope, $slug);
        }

        $manifestVersion = (string) ($manifest['version'] ?? '0.0.0');

        // ── 1. Integrity + signature + compatibility guard ──────────────────
        $guard = (new CoreModuleActivationGuard())->assertCanActivate($path, $manifest);
        if (!(bool) ($guard['ok'] ?? false)) {
            return $this->fail(
                'Vérification pre-update échouée: ' . implode(' | ', (array) ($guard['errors'] ?? [])),
                $scope,
                $slug,
                ['integrity' => (array) ($guard['integrity'] ?? []), 'errors' => (array) ($guard['errors'] ?? [])],
                $manifestVersion
            );
        }

        // ── 2. Snapshot pre-update ──────────────────────────────────────────
        (new CoreModuleSnapshotManager())->create($scope, $slug, 'pre-update', 'updater');

        // ── 3. Migrations ───────────────────────────────────────────────────
        $migrations = (new CoreModuleMigrationRunner())->run($path);

        // ── 4. Sync DB record (version + preserve enabled state) ────────────
        $stateStore = new CoreModuleStateStore();
        $stateBySlug = $stateStore->stateBySlug();
        $dbRow = $stateBySlug[$slug] ?? null;
        $dbVersionBefore = (string) ($dbRow['version'] ?? 'non installé');
        $currentlyEnabled = isset($dbRow['status']) && (string) $dbRow['status'] === 'active';

        $stateStore->persist(
            $slug,
            (string) ($manifest['name'] ?? $slug),
            $manifestVersion,
            $currentlyEnabled
        );

        require_once CATMIN_CORE . '/events-bus.php';
        catmin_event_emit('module.updated', [
            'scope'           => $scope,
            'slug'            => $slug,
            'version_before'  => $dbVersionBefore,
            'version_after'   => $manifestVersion,
            'migrations_run'  => (array) ($migrations['executed'] ?? []),
        ]);

        Core\logs\Logger::info('Module mis à jour', [
            'scope'    => $scope,
            'slug'     => $slug,
            'from'     => $dbVersionBefore,
            'to'       => $manifestVersion,
        ]);

        return [
            'ok'               => true,
            'message'          => 'Module mis à jour avec succès',
            'scope'            => $scope,
            'slug'             => $slug,
            'manifest_version' => $manifestVersion,
            'db_version_before'=> $dbVersionBefore,
            'integrity'        => (array) ($guard['integrity'] ?? []),
            'migrations'       => (array) ($migrations['executed'] ?? []),
            'errors'           => [],
        ];
    }

    /** @return array{ok:false, message:string, scope:string, slug:string, manifest_version:string, db_version_before:string, integrity:array, migrations:array, errors:array<int,string>} */
    private function fail(string $message, string $scope, string $slug, array $extra = [], string $manifestVersion = ''): array
    {
        return [
            'ok'               => false,
            'message'          => $message,
            'scope'            => $scope,
            'slug'             => $slug,
            'manifest_version' => $manifestVersion,
            'db_version_before'=> (string) ($extra['db_version_before'] ?? ''),
            'integrity'        => (array) ($extra['integrity'] ?? []),
            'migrations'       => [],
            'errors'           => (array) ($extra['errors'] ?? [$message]),
        ];
    }
}
