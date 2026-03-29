<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use App\Services\Performance\PerformanceReportService;
use App\Services\SecurityHardeningService;
use Modules\Logger\Models\MonitoringIncident;
use Modules\Logger\Models\MonitoringSnapshot;
use Modules\Logger\Models\SystemAlert;

class MonitoringService
{
    /**
     * @return array<string, mixed>
     */
    public function buildDashboardReport(int $limitIncidents = 20): array
    {
        $checks = $this->collectChecks();
        $history = $this->snapshotHistory();
        $healthScore = app(SystemHealthScoreService::class)->build($checks, $history);
        $globalStatus = (string) ($healthScore['status'] ?? self::computeGlobalStatus($checks));
        $score = (int) ($healthScore['score'] ?? self::computeScore($checks));

        $incidents = MonitoringIncident::query()
            ->whereIn('status', ['warning', 'degraded', 'critical'])
            ->orderByRaw("FIELD(status, 'critical', 'degraded', 'warning')")
            ->orderByDesc('last_seen_at')
            ->limit(max(1, $limitIncidents))
            ->get();

        return [
            'global' => [
                'status' => $globalStatus,
                'score' => $score,
                'label' => (string) ($healthScore['label'] ?? 'Stable'),
                'confidence' => (int) ($healthScore['confidence'] ?? 100),
                'checked_at' => now()->toIso8601String(),
            ],
            'health_score' => $healthScore,
            'checks' => $checks,
            'incidents' => $incidents,
            'alert_clusters' => $this->alertClusters(24),
            'history' => $history,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function captureSnapshot(): array
    {
        $checks = $this->collectChecks();
        $healthScore = app(SystemHealthScoreService::class)->build($checks, $this->snapshotHistory());
        $globalStatus = (string) ($healthScore['status'] ?? self::computeGlobalStatus($checks));
        $score = (int) ($healthScore['score'] ?? self::computeScore($checks));

        $this->syncIncidentsFromChecks($checks);

        $openIncidentCount = MonitoringIncident::query()
            ->whereIn('status', ['warning', 'degraded', 'critical'])
            ->count();

        $criticalIncidentCount = MonitoringIncident::query()
            ->where('status', 'critical')
            ->count();

        MonitoringSnapshot::query()->create([
            'global_status' => $globalStatus,
            'score' => $score,
            'checks_json' => $checks,
            'incidents_open' => $openIncidentCount,
            'incidents_critical' => $criticalIncidentCount,
        ]);

        return [
            'status' => $globalStatus,
            'score' => $score,
            'health_score' => $healthScore,
            'checks' => $checks,
            'incidents_open' => $openIncidentCount,
            'incidents_critical' => $criticalIncidentCount,
        ];
    }

    public function pruneSnapshots(int $days = 30): int
    {
        return MonitoringSnapshot::query()
            ->where('created_at', '<=', now()->subDays(max(1, $days)))
            ->delete();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function collectChecks(): array
    {
        $checks = [];

        $checks[] = $this->checkDatabase();
        $checks[] = $this->checkStorage();
        $checks[] = $this->checkQueue();
        $checks[] = $this->checkWebhooks();
        $checks[] = $this->checkLogs();
        $checks[] = $this->checkPerformance();
        $checks[] = $this->checkApi();
        $checks[] = $this->checkMailer();
        $checks[] = $this->checkCriticalModules();
        $checks[] = $this->checkSecurityHardening();

        return $checks;
    }

    /**
     * @param array<int, array<string, mixed>> $checks
     */
    public static function computeGlobalStatus(array $checks): string
    {
        $ranks = ['ok' => 0, 'warning' => 1, 'degraded' => 2, 'critical' => 3];
        $maxRank = 0;

        foreach ($checks as $check) {
            $status = (string) ($check['status'] ?? 'ok');
            $maxRank = max($maxRank, $ranks[$status] ?? 0);
        }

        return array_search($maxRank, $ranks, true) ?: 'ok';
    }

    /**
     * @param array<int, array<string, mixed>> $checks
     */
    public static function computeScore(array $checks): int
    {
        $score = 100;

        foreach ($checks as $check) {
            $status = (string) ($check['status'] ?? 'ok');
            $score -= match ($status) {
                'critical' => 28,
                'degraded' => 14,
                'warning' => 6,
                default => 0,
            };
        }

        return max(0, min(100, $score));
    }

    public static function classifyByThreshold(int $metric, int $warning, int $degraded, int $critical): string
    {
        if ($metric >= $critical) {
            return 'critical';
        }

        if ($metric >= $degraded) {
            return 'degraded';
        }

        if ($metric >= $warning) {
            return 'warning';
        }

        return 'ok';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function correlateRows(array $rows): array
    {
        $grouped = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $domain = (string) ($row['domain'] ?? 'system');
            $title = trim((string) ($row['title'] ?? 'Incident'));
            $severity = (string) ($row['severity'] ?? 'warning');
            $message = trim((string) ($row['message'] ?? ''));
            $key = sha1($domain . '|' . $title . '|' . $severity);

            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'domain' => $domain,
                    'title' => $title,
                    'severity' => $severity,
                    'message' => $message,
                    'occurrences' => 0,
                ];
            }

            $grouped[$key]['occurrences']++;
        }

        return array_values($grouped);
    }

    /**
     * @param array<int, array<string, mixed>> $checks
     */
    private function syncIncidentsFromChecks(array $checks): void
    {
        foreach ($checks as $check) {
            $domain = (string) ($check['domain'] ?? 'system');
            $title = (string) ($check['title'] ?? 'Monitoring alert');
            $message = (string) ($check['message'] ?? '');
            $status = (string) ($check['status'] ?? 'ok');
            $fingerprint = sha1($domain . '|' . $title);

            $incident = MonitoringIncident::query()->where('fingerprint', $fingerprint)->first();

            if ($status === 'ok') {
                if ($incident && $incident->status !== 'recovered') {
                    $incident->update([
                        'status' => 'recovered',
                        'recovered_at' => now(),
                        'last_seen_at' => now(),
                    ]);
                }

                continue;
            }

            if (!$incident) {
                MonitoringIncident::query()->create([
                    'fingerprint' => $fingerprint,
                    'domain' => $domain,
                    'severity' => $status,
                    'status' => $status,
                    'title' => $title,
                    'message' => $message,
                    'occurrences' => 1,
                    'first_seen_at' => now(),
                    'last_seen_at' => now(),
                    'context' => [
                        'metric' => $check['metric'] ?? null,
                        'threshold' => $check['threshold'] ?? null,
                        'actions' => $check['actions'] ?? [],
                    ],
                ]);

                continue;
            }

            $incident->update([
                'severity' => $status,
                'status' => $status,
                'title' => $title,
                'message' => $message,
                'occurrences' => (int) $incident->occurrences + 1,
                'last_seen_at' => now(),
                'recovered_at' => null,
                'context' => [
                    'metric' => $check['metric'] ?? null,
                    'threshold' => $check['threshold'] ?? null,
                    'actions' => $check['actions'] ?? [],
                ],
            ]);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function alertClusters(int $hours = 24): array
    {
        $rows = SystemAlert::query()
            ->where('created_at', '>=', now()->subHours(max(1, $hours)))
            ->where('acknowledged', false)
            ->get(['alert_type', 'severity', 'title', 'message'])
            ->map(function (SystemAlert $alert): array {
                return [
                    'domain' => (string) $alert->alert_type,
                    'title' => (string) $alert->title,
                    'severity' => (string) $alert->severity,
                    'message' => (string) $alert->message,
                ];
            })
            ->values()
            ->all();

        return self::correlateRows($rows);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function snapshotHistory(): array
    {
        $rows = MonitoringSnapshot::query()
            ->orderByDesc('id')
            ->limit(24)
            ->get()
            ->values();

        return $rows
            ->map(function (MonitoringSnapshot $snapshot, int $index) use ($rows): array {
                $previous = $rows->get($index + 1);
                $delta = $previous instanceof MonitoringSnapshot
                    ? (int) $snapshot->score - (int) $previous->score
                    : 0;

                return [
                    'status' => (string) $snapshot->global_status,
                    'score' => (int) $snapshot->score,
                    'incidents_open' => (int) $snapshot->incidents_open,
                    'incidents_critical' => (int) $snapshot->incidents_critical,
                    'delta' => $delta,
                    'created_at' => optional($snapshot->created_at)?->toIso8601String(),
                ];
            })
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function checkDatabase(): array
    {
        $ok = true;
        $message = 'Connexion base de donnees stable.';

        try {
            DB::connection()->getPdo();
        } catch (\Throwable $throwable) {
            $ok = false;
            $message = 'Connexion DB echouee: ' . $throwable->getMessage();
        }

        return $this->checkRow(
            domain: 'database',
            status: $ok ? 'ok' : 'critical',
            title: 'Etat base de donnees',
            message: $message,
            metric: $ok ? 1 : 0,
            threshold: 1,
            actions: []
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function checkStorage(): array
    {
        $paths = [
            storage_path(),
            storage_path('framework/cache'),
            storage_path('logs'),
            storage_path('app/public'),
        ];

        $invalid = collect($paths)->filter(fn (string $path): bool => !File::exists($path) || !is_writable($path))->values()->all();

        $ok = $invalid === [];

        return $this->checkRow(
            domain: 'storage',
            status: $ok ? 'ok' : 'degraded',
            title: 'Etat storage',
            message: $ok ? 'Permissions storage OK.' : 'Dossiers non accessibles: ' . implode(', ', $invalid),
            metric: count($invalid),
            threshold: 0,
            actions: []
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function checkQueue(): array
    {
        $failedJobs = 0;
        try {
            $failedJobs = DB::table((string) config('queue.failed.table', 'failed_jobs'))->count();
        } catch (\Throwable) {
            $failedJobs = 0;
        }

        $warning = max(1, (int) SettingService::get('ops.failed_jobs_threshold', 5));
        $status = self::classifyByThreshold($failedJobs, $warning, $warning * 2, $warning * 4);

        return $this->checkRow(
            domain: 'queue',
            status: $status,
            title: 'Failed jobs',
            message: sprintf('failed_jobs=%d, seuil_warning=%d', $failedJobs, $warning),
            metric: $failedJobs,
            threshold: $warning,
            actions: [
                $this->actionRow('Ouvrir Queue', 'admin.queue.index'),
            ]
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function checkWebhooks(): array
    {
        $failed = 0;
        try {
            if (DB::getSchemaBuilder()->hasTable('webhook_deliveries')) {
                $failed = DB::table('webhook_deliveries')
                    ->whereIn('status', ['failed', 'retrying'])
                    ->where('created_at', '>=', now()->subDay())
                    ->count();
            }
        } catch (\Throwable) {
            $failed = 0;
        }

        $warning = max(1, (int) SettingService::get('ops.webhook_failures_threshold', 3));
        $status = self::classifyByThreshold($failed, $warning, $warning * 2, $warning * 4);

        return $this->checkRow(
            domain: 'webhooks',
            status: $status,
            title: 'Webhooks echec/retry 24h',
            message: sprintf('deliveries KO/retrying=%d, seuil_warning=%d', $failed, $warning),
            metric: $failed,
            threshold: $warning,
            actions: [
                $this->actionRow('Ouvrir Webhooks', 'admin.webhooks.index'),
            ]
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function checkLogs(): array
    {
        $errors = 0;

        try {
            $errors = DB::table('system_logs')
                ->whereIn('level', ['error', 'critical', 'alert', 'emergency'])
                ->where('created_at', '>=', now()->subHour())
                ->count();
        } catch (\Throwable) {
            $errors = 0;
        }

        $status = self::classifyByThreshold($errors, 1, 8, 20);

        return $this->checkRow(
            domain: 'logs',
            status: $status,
            title: 'Erreurs critiques recentes',
            message: 'Erreurs critiques sur la derniere heure: ' . $errors,
            metric: $errors,
            threshold: 1,
            actions: [
                $this->actionRow('Ouvrir Logs', 'admin.logger.index'),
                $this->actionRow('Voir Alertes', 'admin.logger.alerts.index'),
            ]
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function checkApi(): array
    {
        $internalPrefix = trim((string) config('catmin.api.prefix', 'api/internal'));
        $ok = $internalPrefix !== '';
        $warningCount = 0;

        try {
            if (DB::getSchemaBuilder()->hasTable('system_logs')) {
                $warningCount = DB::table('system_logs')
                    ->where('channel', 'api')
                    ->whereIn('level', ['warning', 'error', 'critical', 'alert'])
                    ->where('created_at', '>=', now()->subHour())
                    ->count();
            }
        } catch (\Throwable) {
            $warningCount = 0;
        }

        $status = !$ok ? 'warning' : self::classifyByThreshold($warningCount, 3, 8, 15);

        return $this->checkRow(
            domain: 'api',
            status: $status,
            title: 'Etat API',
            message: $ok
                ? sprintf('prefix interne OK (%s), warnings_api_1h=%d', $internalPrefix, $warningCount)
                : 'Prefixe API interne incomplet.',
            metric: $ok ? $warningCount : 1,
            threshold: 3,
            actions: []
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function checkPerformance(): array
    {
        $report = app(PerformanceReportService::class)->buildReport(24);
        $summary = (array) ($report['summary'] ?? []);
        $breaches = (int) ($summary['budget_breaches'] ?? 0);
        $status = self::classifyByThreshold($breaches, 1, 4, 8);

        return $this->checkRow(
            domain: 'performance',
            status: $breaches === 0 ? 'ok' : $status,
            title: 'Performance budgets 24h',
            message: sprintf('budget_breaches=%d, slow_requests=%d', $breaches, (int) ($summary['slow_requests'] ?? 0)),
            metric: $breaches,
            threshold: 1,
            actions: [
                $this->actionRow('Ouvrir Performance', 'admin.performance.index'),
            ]
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function checkMailer(): array
    {
        $failed = 0;

        try {
            if (DB::getSchemaBuilder()->hasTable('mailer_history')) {
                $failed = DB::table('mailer_history')
                    ->where('status', 'failed')
                    ->where('created_at', '>=', now()->subDay())
                    ->count();
            }
        } catch (\Throwable) {
            $failed = 0;
        }

        $status = self::classifyByThreshold($failed, 1, 5, 12);

        return $this->checkRow(
            domain: 'mailer',
            status: $status,
            title: 'Echecs mailer 24h',
            message: 'Echecs mail sur 24h: ' . $failed,
            metric: $failed,
            threshold: 1,
            actions: [
                $this->actionRow('Ouvrir Mailer', 'admin.mailer.manage'),
            ]
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function checkSecurityHardening(): array
    {
        $service = app(SecurityHardeningService::class);
        $checks = $service->collectGuardrails();
        $summary = $service->summarize($checks);

        return $this->checkRow(
            domain: 'security',
            status: (string) ($summary['status'] ?? 'ok'),
            title: 'Guardrails securite production',
            message: sprintf(
                'critical=%d, warning=%d sur %d check(s)',
                (int) ($summary['critical'] ?? 0),
                (int) ($summary['warning'] ?? 0),
                (int) ($summary['total'] ?? 0)
            ),
            metric: (int) (($summary['critical'] ?? 0) + ($summary['warning'] ?? 0)),
            threshold: 0,
            actions: [
                $this->actionRow('Ouvrir Parametres', 'admin.settings.manage'),
            ]
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function checkCriticalModules(): array
    {
        $critical = ['logger', 'queue', 'webhooks', 'mailer', 'shop'];
        $inactive = collect($critical)
            ->filter(fn (string $slug): bool => !ModuleManager::isEnabled($slug))
            ->values()
            ->all();

        $count = count($inactive);
        $status = $count === 0 ? 'ok' : ($count >= 3 ? 'critical' : 'degraded');

        return $this->checkRow(
            domain: 'modules',
            status: $status,
            title: 'Modules critiques actifs',
            message: $count === 0 ? 'Tous les modules critiques sont actifs.' : 'Inactifs: ' . implode(', ', $inactive),
            metric: $count,
            threshold: 0,
            actions: [
                $this->actionRow('Ouvrir Modules', 'admin.modules.index'),
            ]
        );
    }

    /**
     * @param array<int, array<string, mixed>> $actions
     * @return array<string, mixed>
     */
    private function checkRow(string $domain, string $status, string $title, string $message, int $metric, int $threshold, array $actions): array
    {
        return [
            'status' => $status,
            'domain' => $domain,
            'title' => $title,
            'message' => $message,
            'metric' => $metric,
            'threshold' => $threshold,
            'checked_at' => now()->toIso8601String(),
            'actions' => array_values(array_filter($actions, fn (array $row): bool => (string) Arr::get($row, 'url', '') !== '')),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function actionRow(string $label, string $routeName): array
    {
        if (!Route::has($routeName)) {
            return ['label' => $label, 'route' => $routeName, 'url' => ''];
        }

        return [
            'label' => $label,
            'route' => $routeName,
            'url' => route($routeName),
        ];
    }
}
