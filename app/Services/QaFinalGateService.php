<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class QaFinalGateService
{
    /**
     * @return array<string, mixed>
     */
    public static function run(bool $withAutomatedTests = false, bool $strictManual = false): array
    {
        $validation = ValidationV2PlusService::run($withAutomatedTests, false);
        $rbac = RbacAuditService::generate();
        $security = self::securityChecks();
        $performance = self::performanceChecks();
        $ux = self::uxChecks();
        $manual = self::manualChecklist();
        $automated = self::automatedChecks($validation, $rbac, $security, $performance);
        $releaseCriteria = self::releaseCriteria($automated, $manual, $strictManual);

        $blockers = [];
        foreach ($automated as $check) {
            if (($check['severity'] ?? 'warning') === 'critical' && !(bool) ($check['ok'] ?? false)) {
                $blockers[] = (string) ($check['label'] ?? 'check');
            }
        }

        if ($strictManual) {
            foreach ($manual as $row) {
                if (($row['severity'] ?? 'warning') === 'critical' && ($row['status'] ?? 'todo') !== 'pass') {
                    $blockers[] = 'manual:' . (string) ($row['id'] ?? 'item');
                }
            }
        }

        foreach ($releaseCriteria as $criterion) {
            if (!(bool) ($criterion['ok'] ?? false) && ($criterion['severity'] ?? 'warning') === 'critical') {
                $blockers[] = 'release:' . (string) ($criterion['id'] ?? 'criterion');
            }
        }

        $status = $blockers === [] ? 'READY' : 'NOT READY';

        return [
            'generated_at' => Carbon::now()->toIso8601String(),
            'status' => $status,
            'strict_manual' => $strictManual,
            'with_automated_tests' => $withAutomatedTests,
            'summary' => [
                'automated_total' => count($automated),
                'automated_ok' => collect($automated)->where('ok', true)->count(),
                'manual_total' => count($manual),
                'manual_passed' => collect($manual)->where('status', 'pass')->count(),
                'manual_pending' => collect($manual)->where('status', 'todo')->count(),
                'blockers' => count($blockers),
            ],
            'blockers' => $blockers,
            'sections' => [
                'v2_checklist' => self::v2Checklist($validation, $rbac, $security, $performance, $ux),
                'automated_tests' => [
                    'validation_v2_plus' => $validation,
                    'checks' => $automated,
                ],
                'manual_tests' => $manual,
                'security_validation' => $security,
                'performance_validation' => $performance,
                'ux_validation' => $ux,
                'release_criteria' => $releaseCriteria,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $report
     * @return array{json:string, markdown:string}
     */
    public static function writeReport(array $report): array
    {
        $timestamp = Carbon::now()->format('Ymd-His');
        $baseName = 'qa-final-gate-' . $timestamp;
        $dir = 'reports';

        Storage::disk('local')->makeDirectory($dir);

        $jsonPath = $dir . '/' . $baseName . '.json';
        $mdPath = $dir . '/' . $baseName . '.md';

        Storage::disk('local')->put(
            $jsonPath,
            json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n"
        );
        Storage::disk('local')->put($mdPath, self::toMarkdown($report));

        return [
            'json' => storage_path('app/' . $jsonPath),
            'markdown' => storage_path('app/' . $mdPath),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function v2Checklist(array $validation, array $rbac, array $security, array $performance, array $ux): array
    {
        return [
            [
                'id' => 'v2_integrity',
                'label' => 'Integrite technique V2',
                'ok' => (bool) ($validation['ok'] ?? false),
                'details' => 'Install/health/modules/addons/migrations via catmin:validate:v2-plus',
            ],
            [
                'id' => 'v2_rbac',
                'label' => 'Couverture RBAC',
                'ok' => ((int) data_get($rbac, 'summary.sensitive_routes_unprotected', 1) === 0)
                    && ((int) data_get($rbac, 'summary.inconsistent_routes', 1) === 0),
                'details' => 'Routes sensibles protegees et mapping coherent.',
            ],
            [
                'id' => 'v2_security',
                'label' => 'Validation securite',
                'ok' => collect($security)->where('ok', false)->where('severity', 'critical')->count() === 0,
                'details' => 'Guardrails prod + hygiene securite critiques.',
            ],
            [
                'id' => 'v2_performance',
                'label' => 'Validation performance',
                'ok' => collect($performance)->where('ok', false)->where('severity', 'critical')->count() === 0,
                'details' => 'Seuils perf de base et signaux de saturation.',
            ],
            [
                'id' => 'v2_ux',
                'label' => 'Validation UX',
                'ok' => collect($ux)->where('ok', false)->where('severity', 'critical')->count() === 0,
                'details' => 'Checks UX critiques des parcours admin.',
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function automatedChecks(array $validation, array $rbac, array $security, array $performance): array
    {
        return [
            [
                'id' => 'auto_v2_validation',
                'label' => 'Validation V2+',
                'ok' => (bool) ($validation['ok'] ?? false),
                'severity' => 'critical',
                'details' => sprintf(
                    '%d NOK sur %d checks.',
                    (int) data_get($validation, 'summary.nok', 0),
                    (int) data_get($validation, 'summary.total', 0)
                ),
            ],
            [
                'id' => 'auto_rbac_coverage',
                'label' => 'RBAC coverage',
                'ok' => ((int) data_get($rbac, 'summary.sensitive_routes_unprotected', 1) === 0)
                    && ((int) data_get($rbac, 'summary.inconsistent_routes', 1) === 0),
                'severity' => 'critical',
                'details' => sprintf(
                    'coverage=%s%%, unprotected=%d, inconsistent=%d',
                    (string) data_get($rbac, 'summary.sensitive_coverage_percent', '0'),
                    (int) data_get($rbac, 'summary.sensitive_routes_unprotected', 0),
                    (int) data_get($rbac, 'summary.inconsistent_routes', 0)
                ),
            ],
            [
                'id' => 'auto_security_critical',
                'label' => 'Security checks critiques',
                'ok' => collect($security)->where('ok', false)->where('severity', 'critical')->count() === 0,
                'severity' => 'critical',
                'details' => 'Erreurs critiques securite: ' . collect($security)->where('ok', false)->where('severity', 'critical')->count(),
            ],
            [
                'id' => 'auto_performance_critical',
                'label' => 'Performance checks critiques',
                'ok' => collect($performance)->where('ok', false)->where('severity', 'critical')->count() === 0,
                'severity' => 'warning',
                'details' => 'Erreurs critiques perf: ' . collect($performance)->where('ok', false)->where('severity', 'critical')->count(),
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function securityChecks(): array
    {
        $checks = [];

        $isProd = app()->environment('production');
        $appDebug = (bool) config('app.debug', false);
        $checks[] = [
            'id' => 'security_app_debug',
            'label' => 'APP_DEBUG policy',
            'ok' => !$isProd || !$appDebug,
            'severity' => 'critical',
            'details' => $isProd && $appDebug ? 'APP_DEBUG actif en production.' : 'Conforme.',
        ];

        $appKey = (string) config('app.key', '');
        $checks[] = [
            'id' => 'security_app_key',
            'label' => 'APP_KEY presence',
            'ok' => $appKey !== '',
            'severity' => 'critical',
            'details' => $appKey !== '' ? 'Cle presente.' : 'APP_KEY manquante.',
        ];

        $apiPrefix = trim((string) config('catmin.api.prefix', 'api/internal'));
        $checks[] = [
            'id' => 'security_api_prefix',
            'label' => 'API interne prefix',
            'ok' => $apiPrefix !== '',
            'severity' => 'warning',
            'details' => $apiPrefix !== '' ? 'Prefixe configure: ' . $apiPrefix : 'Prefixe API interne vide.',
        ];

        return $checks;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function performanceChecks(): array
    {
        $checks = [];

        $hasOpcache = extension_loaded('Zend OPcache');
        $checks[] = [
            'id' => 'perf_opcache_extension',
            'label' => 'OPcache active',
            'ok' => $hasOpcache,
            'severity' => 'warning',
            'details' => $hasOpcache ? 'Extension OPcache detectee.' : 'OPcache absente.',
        ];

        $failedJobs = 0;
        try {
            $failedJobs = DB::table((string) config('queue.failed.table', 'failed_jobs'))->count();
        } catch (\Throwable) {
            $failedJobs = 0;
        }

        $checks[] = [
            'id' => 'perf_failed_jobs_threshold',
            'label' => 'Backlog jobs echecs',
            'ok' => $failedJobs <= 100,
            'severity' => 'warning',
            'details' => sprintf('failed_jobs=%d (seuil warning=100)', $failedJobs),
        ];

        return $checks;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function uxChecks(): array
    {
        $checks = [];

        $checks[] = [
            'id' => 'ux_admin_css_present',
            'label' => 'Theme admin compile',
            'ok' => file_exists(public_path('build/manifest.json')),
            'severity' => 'warning',
            'details' => file_exists(public_path('build/manifest.json'))
                ? 'Assets Vite detectes.'
                : 'manifest build manquant.',
        ];

        $checks[] = [
            'id' => 'ux_manual_walkthrough_required',
            'label' => 'Walkthrough UX manuel',
            'ok' => true,
            'severity' => 'critical',
            'details' => 'A valider via checklist manuelle (dashboards, CRUD, erreurs, mobile).',
        ];

        return $checks;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function manualChecklist(): array
    {
        return [
            [
                'id' => 'manual_auth_journey',
                'label' => 'Login/logout + timeout session',
                'area' => 'security',
                'severity' => 'critical',
                'status' => 'todo',
                'notes' => 'Verifier login, logout, expiration session et erreurs 403/419.',
            ],
            [
                'id' => 'manual_rbac_negative',
                'label' => 'RBAC cas negatifs',
                'area' => 'security',
                'severity' => 'critical',
                'status' => 'todo',
                'notes' => 'Verifier qu un role sans permission ne peut pas executer actions sensibles.',
            ],
            [
                'id' => 'manual_content_ux',
                'label' => 'UX Pages/Articles/Media',
                'area' => 'ux',
                'severity' => 'warning',
                'status' => 'todo',
                'notes' => 'Controle lisibilite, feedback notifications, coherent mobile/desktop.',
            ],
            [
                'id' => 'manual_shop_mailer_flow',
                'label' => 'Flux Shop -> Facture PDF -> Mailer',
                'area' => 'release',
                'severity' => 'critical',
                'status' => 'todo',
                'notes' => 'Creer commande, generer facture PDF, envoi email et journalisation.',
            ],
            [
                'id' => 'manual_perf_smoke',
                'label' => 'Smoke perf admin',
                'area' => 'performance',
                'severity' => 'warning',
                'status' => 'todo',
                'notes' => 'Verifier latence percue dashboard/listings et absence de blocage UI.',
            ],
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $automated
     * @param array<int, array<string, mixed>> $manual
     * @return array<int, array<string, mixed>>
     */
    private static function releaseCriteria(array $automated, array $manual, bool $strictManual): array
    {
        $autoCriticalOk = collect($automated)
            ->filter(fn (array $check): bool => ($check['severity'] ?? 'warning') === 'critical')
            ->every(fn (array $check): bool => (bool) ($check['ok'] ?? false));

        $manualCriticalOk = collect($manual)
            ->filter(fn (array $row): bool => ($row['severity'] ?? 'warning') === 'critical')
            ->every(fn (array $row): bool => ($row['status'] ?? 'todo') === 'pass');

        $testsOk = self::runFocusedPhpunitSmoke();

        return [
            [
                'id' => 'release_auto_critical',
                'label' => 'Checks automatiques critiques',
                'ok' => $autoCriticalOk,
                'severity' => 'critical',
                'details' => $autoCriticalOk ? 'OK' : 'Au moins un check auto critique en echec.',
            ],
            [
                'id' => 'release_manual_signoff',
                'label' => 'Sign-off manuel critique',
                'ok' => $strictManual ? $manualCriticalOk : true,
                'severity' => 'critical',
                'details' => $strictManual
                    ? ($manualCriticalOk ? 'Tous les checks manuels critiques sont PASS.' : 'Checks manuels critiques incomplets.')
                    : 'Mode informatif (activer --strict-manual pour bloquer).',
            ],
            [
                'id' => 'release_focused_tests',
                'label' => 'Tests smoke focus',
                'ok' => $testsOk['ok'],
                'severity' => 'warning',
                'details' => $testsOk['details'],
            ],
        ];
    }

    /**
     * @return array{ok:bool, details:string}
     */
    private static function runFocusedPhpunitSmoke(): array
    {
        $command = ['php', 'artisan', 'test', 'tests/Feature/MailerAdminServiceTest.php'];
        $process = new Process($command, base_path(), null, null, 120);

        try {
            $process->run();
            if ($process->isSuccessful()) {
                return ['ok' => true, 'details' => 'tests/Feature/MailerAdminServiceTest.php OK'];
            }

            return ['ok' => false, 'details' => 'Echec test smoke (code=' . ((int) $process->getExitCode()) . ')'];
        } catch (\Throwable $throwable) {
            return ['ok' => false, 'details' => 'Execution tests impossible: ' . $throwable->getMessage()];
        }
    }

    /**
     * @param array<string, mixed> $report
     */
    private static function toMarkdown(array $report): string
    {
        $lines = [];
        $lines[] = '# CATMIN QA Final Gate Report';
        $lines[] = '';
        $lines[] = '- Generated at: ' . (string) ($report['generated_at'] ?? '');
        $lines[] = '- Status: **' . (string) ($report['status'] ?? 'NOT READY') . '**';
        $lines[] = '- Strict manual mode: ' . ((bool) ($report['strict_manual'] ?? false) ? 'on' : 'off');
        $lines[] = '- Automated tests requested: ' . ((bool) ($report['with_automated_tests'] ?? false) ? 'yes' : 'no');
        $lines[] = '';

        $summary = (array) ($report['summary'] ?? []);
        $lines[] = '## Summary';
        $lines[] = '';
        $lines[] = '- Automated checks: ' . (int) ($summary['automated_ok'] ?? 0) . '/' . (int) ($summary['automated_total'] ?? 0) . ' OK';
        $lines[] = '- Manual checks passed: ' . (int) ($summary['manual_passed'] ?? 0) . '/' . (int) ($summary['manual_total'] ?? 0);
        $lines[] = '- Manual pending: ' . (int) ($summary['manual_pending'] ?? 0);
        $lines[] = '- Blockers: ' . (int) ($summary['blockers'] ?? 0);
        $lines[] = '';

        $lines[] = '## Automated Checks';
        $lines[] = '';
        foreach ((array) data_get($report, 'sections.automated_tests.checks', []) as $check) {
            $ok = (bool) ($check['ok'] ?? false);
            $lines[] = '- [' . ($ok ? 'OK' : 'NOK') . '] ' . (string) ($check['label'] ?? 'check') . ' (' . (string) ($check['severity'] ?? 'warning') . ') - ' . (string) ($check['details'] ?? '');
        }
        $lines[] = '';

        $lines[] = '## Manual Checklist';
        $lines[] = '';
        foreach ((array) data_get($report, 'sections.manual_tests', []) as $item) {
            $lines[] = '- [' . strtoupper((string) ($item['status'] ?? 'todo')) . '] ' . (string) ($item['label'] ?? 'item') . ' (' . (string) ($item['severity'] ?? 'warning') . ') - ' . (string) ($item['notes'] ?? '');
        }
        $lines[] = '';

        $lines[] = '## Release Criteria';
        $lines[] = '';
        foreach ((array) data_get($report, 'sections.release_criteria', []) as $criterion) {
            $lines[] = '- [' . ((bool) ($criterion['ok'] ?? false) ? 'OK' : 'NOK') . '] ' . (string) ($criterion['label'] ?? 'criterion') . ' - ' . (string) ($criterion['details'] ?? '');
        }
        $lines[] = '';

        $lines[] = '## Blockers';
        $lines[] = '';
        $blockers = (array) ($report['blockers'] ?? []);
        if ($blockers === []) {
            $lines[] = '- none';
        } else {
            foreach ($blockers as $blocker) {
                $lines[] = '- ' . (string) $blocker;
            }
        }

        $lines[] = '';

        return implode("\n", $lines) . "\n";
    }
}
