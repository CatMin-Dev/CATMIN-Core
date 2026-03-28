<?php

namespace App\Services;

use Symfony\Component\Process\Process;

class ValidationV2PlusService
{
    /**
     * @return array<string, mixed>
     */
    public static function run(bool $withTests = true, bool $deep = false): array
    {
        $install = InstallCheckService::run();
        $health = HealthCheckService::run();
        $moduleIssues = ModuleManager::stateIssues();
        $migrationCollisions = MigrationCollisionService::detectBasenameCollisions();

        $checks = [
            self::checkInstall($install),
            self::checkHealth($health),
            self::checkModules($moduleIssues),
            self::checkAddons(),
            self::checkMigrations($migrationCollisions),
        ];

        $tests = [
            'executed' => false,
            'ok' => true,
            'suite' => $deep ? 'deep' : 'modules',
            'duration_ms' => 0,
            'exit_code' => 0,
            'command' => [],
            'output' => [],
        ];

        if ($withTests) {
            $tests = self::runStabilityTests($deep);
            $checks[] = [
                'key' => 'stability_tests',
                'label' => 'Tests de stabilite',
                'ok' => (bool) $tests['ok'],
                'details' => $tests['executed']
                    ? sprintf(
                        'Suite=%s, exit_code=%d, duree=%dms.',
                        (string) $tests['suite'],
                        (int) $tests['exit_code'],
                        (int) $tests['duration_ms']
                    )
                    : 'Tests non executes.',
                'metrics' => [
                    'executed' => (bool) $tests['executed'],
                    'suite' => (string) $tests['suite'],
                    'duration_ms' => (int) $tests['duration_ms'],
                    'exit_code' => (int) $tests['exit_code'],
                ],
            ];
        }

        $summary = [
            'total' => count($checks),
            'ok' => collect($checks)->where('ok', true)->count(),
            'nok' => collect($checks)->where('ok', false)->count(),
        ];

        return [
            'ok' => $summary['nok'] === 0,
            'summary' => $summary,
            'checks' => $checks,
            'context' => [
                'modules' => ModuleManager::summary(),
                'addons' => AddonManager::summary(),
                'module_state_issues' => $moduleIssues,
                'migration_collisions' => $migrationCollisions,
            ],
            'tests' => $tests,
        ];
    }

    /**
     * @param array<string, mixed> $install
     * @return array<string, mixed>
     */
    protected static function checkInstall(array $install): array
    {
        return [
            'key' => 'install_requirements',
            'label' => 'Prerequis installation',
            'ok' => (bool) ($install['ok'] ?? false),
            'details' => (bool) ($install['ok'] ?? false)
                ? 'Prerequis installation satisfaits.'
                : sprintf('%d erreur(s) prerequis.', count((array) ($install['errors'] ?? []))),
            'metrics' => [
                'errors' => count((array) ($install['errors'] ?? [])),
            ],
        ];
    }

    /**
     * @param array<string, mixed> $health
     * @return array<string, mixed>
     */
    protected static function checkHealth(array $health): array
    {
        $summary = (array) ($health['summary'] ?? []);

        return [
            'key' => 'health_checks',
            'label' => 'Sante systeme',
            'ok' => (bool) ($health['ok'] ?? false),
            'details' => sprintf(
                '%d/%d checks OK.',
                (int) ($summary['ok'] ?? 0),
                (int) ($summary['total'] ?? 0)
            ),
            'metrics' => [
                'ok' => (int) ($summary['ok'] ?? 0),
                'total' => (int) ($summary['total'] ?? 0),
                'nok' => (int) ($summary['nok'] ?? 0),
            ],
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $issues
     * @return array<string, mixed>
     */
    protected static function checkModules(array $issues): array
    {
        $critical = collect($issues)->where('level', 'critical')->count();
        $warning = collect($issues)->where('level', 'warning')->count();
        $enabled = ModuleManager::enabledCount();
        $total = ModuleManager::all()->count();

        return [
            'key' => 'module_integrity',
            'label' => 'Integrite modules',
            'ok' => $critical === 0,
            'details' => sprintf('%d/%d modules actifs, %d critique(s), %d warning(s).', $enabled, $total, $critical, $warning),
            'metrics' => [
                'enabled' => $enabled,
                'total' => $total,
                'critical_issues' => $critical,
                'warning_issues' => $warning,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected static function checkAddons(): array
    {
        $invalidVersion = 0;
        $missingStructure = 0;
        $dependencyIssues = 0;

        foreach (AddonManager::all() as $addon) {
            if (!(bool) ($addon->version_valid ?? false)) {
                $invalidVersion++;
            }

            if (AddonManager::missingStructure($addon) !== []) {
                $missingStructure++;
            }

            $canEnable = AddonManager::canEnable((string) $addon->slug);
            if ((bool) ($addon->enabled ?? false) && !$canEnable['allowed']) {
                $dependencyIssues++;
            }
        }

        $ok = ($invalidVersion + $missingStructure + $dependencyIssues) === 0;

        return [
            'key' => 'addon_integrity',
            'label' => 'Integrite addons',
            'ok' => $ok,
            'details' => sprintf(
                '%d version(s) invalides, %d structure(s) incomplete(s), %d dependance(s) invalides.',
                $invalidVersion,
                $missingStructure,
                $dependencyIssues
            ),
            'metrics' => [
                'invalid_versions' => $invalidVersion,
                'missing_structure' => $missingStructure,
                'dependency_issues' => $dependencyIssues,
            ],
        ];
    }

    /**
     * @param array<string, array<int, string>> $collisions
     * @return array<string, mixed>
     */
    protected static function checkMigrations(array $collisions): array
    {
        return [
            'key' => 'migration_collisions',
            'label' => 'Collisions migrations',
            'ok' => empty($collisions),
            'details' => empty($collisions)
                ? 'Aucune collision detectee.'
                : sprintf('%d collision(s) detectee(s).', count($collisions)),
            'metrics' => [
                'collisions' => count($collisions),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected static function runStabilityTests(bool $deep): array
    {
        $command = $deep
            ? ['php', 'artisan', 'test']
            : ['php', 'artisan', 'test', 'tests/Unit/Modules', 'tests/Feature/Modules'];

        $start = microtime(true);
        $process = new Process($command, base_path());
        $process->setTimeout(0);
        $process->run();

        $duration = (int) round((microtime(true) - $start) * 1000);
        $rawOutput = trim($process->getOutput() . "\n" . $process->getErrorOutput());

        return [
            'executed' => true,
            'ok' => $process->isSuccessful(),
            'suite' => $deep ? 'deep' : 'modules',
            'duration_ms' => $duration,
            'exit_code' => (int) $process->getExitCode(),
            'command' => $command,
            'output' => $rawOutput === ''
                ? []
                : array_slice(preg_split('/\R/', $rawOutput) ?: [], -80),
        ];
    }
}
