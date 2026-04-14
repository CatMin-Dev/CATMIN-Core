<?php

declare(strict_types=1);

use Core\database\ConnectionManager;

require_once CATMIN_CORE . '/db-upgrade-runner.php';
require_once CATMIN_CORE . '/db-version-manager.php';
require_once CATMIN_CORE . '/module-runtime-snapshot.php';
require_once CATMIN_CORE . '/module-state-store.php';

final class CoreDbCoherenceGuard
{
    public function run(?string $connectionName = null): array
    {
        $report = [
            'ok' => true,
            'critical' => [],
            'warnings' => [],
            'db' => [
                'upgrade' => [],
                'version_current' => 'unknown',
                'version_expected' => 'unknown',
                'missing_tables' => [],
            ],
            'modules' => [
                'invalid' => [],
                'orphaned_state_rows' => [],
                'state_reconciled' => 0,
            ],
        ];

        try {
            $report['db']['upgrade'] = (new CoreDbUpgradeRunner())->run($connectionName);
        } catch (Throwable $e) {
            $report['ok'] = false;
            $report['critical'][] = 'DB migration/upgrade failed: ' . $e->getMessage();
            Core\logs\Logger::error('DB coherence guard upgrade failure', ['error' => substr($e->getMessage(), 0, 240)]);
            return $report;
        }

        $versions = new CoreDbVersionManager();
        $current = $versions->currentSchemaVersion($connectionName);
        $expected = $versions->expectedSchemaVersion();
        $report['db']['version_current'] = $current;
        $report['db']['version_expected'] = $expected;

        if ($current !== $expected) {
            $report['ok'] = false;
            $report['critical'][] = 'DB version mismatch: current=' . $current . ' expected=' . $expected;
        }

        try {
            $pdo = (new ConnectionManager())->connection($connectionName);
            $corePrefix = (string) config('database.prefixes.core', 'core_');
            $adminPrefix = (string) config('database.prefixes.admin', 'admin_');
            $requiredTables = [
                $corePrefix . 'db_versions',
                $corePrefix . 'settings',
                $corePrefix . 'modules',
                $adminPrefix . 'users',
            ];
            $missingTables = [];
            foreach ($requiredTables as $table) {
                try {
                    $pdo->query('SELECT 1 FROM ' . $table . ' LIMIT 1');
                } catch (Throwable) {
                    $missingTables[] = $table;
                }
            }
            $report['db']['missing_tables'] = $missingTables;
            if ($missingTables !== []) {
                $report['ok'] = false;
                $report['critical'][] = 'Missing required tables: ' . implode(', ', $missingTables);
            }
        } catch (Throwable $e) {
            $report['ok'] = false;
            $report['critical'][] = 'DB connection check failed: ' . $e->getMessage();
        }

        $snapshot = (new CoreModuleRuntimeSnapshot())->all();
        $modules = (array) ($snapshot['modules'] ?? []);
        $stateStore = new CoreModuleStateStore();
        $stateBySlug = $stateStore->stateBySlug();

        $knownSlugs = [];
        foreach ($modules as $module) {
            if (!is_array($module)) {
                continue;
            }

            $manifest = is_array($module['manifest'] ?? null) ? $module['manifest'] : [];
            $slug = strtolower(trim((string) ($manifest['slug'] ?? '')));
            if ($slug === '') {
                continue;
            }

            $knownSlugs[$slug] = true;

            $isValid = (bool) ($module['valid'] ?? false);
            $isCompatible = (bool) ($module['compatible'] ?? false);
            $isEnabled = (bool) ($module['enabled'] ?? false);

            if (!$isValid || !$isCompatible) {
                $report['modules']['invalid'][] = $slug;
            }

            $expectedStatus = $isEnabled ? 'active' : 'inactive';
            $currentStatus = strtolower(trim((string) ($stateBySlug[$slug]['status'] ?? '')));

            if ($currentStatus !== $expectedStatus) {
                $stateStore->persist(
                    $slug,
                    (string) ($manifest['name'] ?? $slug),
                    (string) ($manifest['version'] ?? '0.0.0'),
                    $isEnabled
                );
                $report['modules']['state_reconciled']++;
            }
        }

        foreach (array_keys($stateBySlug) as $dbSlug) {
            if (!isset($knownSlugs[$dbSlug])) {
                $rowState = strtolower(trim((string) ($stateBySlug[$dbSlug]['db_state'] ?? '')));
                if (in_array($rowState, ['uninstalled_keep_data', 'uninstalled_drop_data'], true)) {
                    continue;
                }
                $report['modules']['orphaned_state_rows'][] = $dbSlug;
            }
        }

        if ($report['modules']['invalid'] !== []) {
            $report['warnings'][] = 'Invalid/incompatible modules: ' . implode(', ', $report['modules']['invalid']);
        }
        if ($report['modules']['orphaned_state_rows'] !== []) {
            $report['warnings'][] = 'Orphaned module state rows: ' . implode(', ', $report['modules']['orphaned_state_rows']);
        }

        if ($report['ok']) {
            Core\logs\Logger::info('DB coherence guard: OK', [
                'db_version' => $current,
                'reconciled' => $report['modules']['state_reconciled'],
            ]);
        } else {
            Core\logs\Logger::error('DB coherence guard: critical incoherence', [
                'critical' => $report['critical'],
                'warnings' => $report['warnings'],
            ]);
        }

        return $report;
    }
}