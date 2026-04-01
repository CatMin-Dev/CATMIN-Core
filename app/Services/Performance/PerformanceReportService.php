<?php

namespace App\Services\Performance;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Modules\Logger\Models\SystemLog;

class PerformanceReportService
{
    public function __construct(private readonly PerformanceBudgetService $budgetService)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function buildReport(int $hours = 24): array
    {
        if (!Schema::hasTable('system_logs')) {
            return [
                'generated_at' => now()->toIso8601String(),
                'window_hours' => max(1, $hours),
                'summary' => [
                    'requests_profiled' => 0,
                    'slow_requests' => 0,
                    'budget_breaches' => 0,
                    'slow_queries' => 0,
                    'long_jobs' => 0,
                ],
                'budgets' => [],
                'routes' => [],
                'slow_queries_top' => [],
                'long_jobs_top' => [],
                'recommendations' => [
                    'Table system_logs absente: executer les migrations Logger.',
                ],
            ];
        }

        $from = now()->subHours(max(1, $hours));

        $requestLogs = SystemLog::query()
            ->where('channel', 'performance')
            ->where('event', 'http.request.performance')
            ->where('created_at', '>=', $from)
            ->orderByDesc('id')
            ->limit(1000)
            ->get();

        $slowQueryLogs = SystemLog::query()
            ->where('channel', 'performance')
            ->where('event', 'db.query.slow')
            ->where('created_at', '>=', $from)
            ->orderByDesc('id')
            ->limit(500)
            ->get();

        $jobLogs = SystemLog::query()
            ->where('channel', 'performance')
            ->where('event', 'queue.job.performance')
            ->where('created_at', '>=', $from)
            ->orderByDesc('id')
            ->limit(300)
            ->get();

        $routes = $this->aggregateRoutes($requestLogs->all());
        $budgets = $this->aggregateBudgets($requestLogs->all());
        $slowQueries = $this->aggregateSlowQueries($slowQueryLogs->all());
        $jobs = $this->aggregateJobs($jobLogs->all());

        $budgetBreaches = collect($budgets)->sum(fn (array $row): int => (int) ($row['breaches'] ?? 0));
        $slowRequests = collect($routes)->sum(fn (array $row): int => (int) ($row['slow_requests'] ?? 0));

        return [
            'generated_at' => now()->toIso8601String(),
            'window_hours' => max(1, $hours),
            'summary' => [
                'requests_profiled' => $requestLogs->count(),
                'slow_requests' => $slowRequests,
                'budget_breaches' => (int) $budgetBreaches,
                'slow_queries' => count($slowQueries),
                'long_jobs' => count($jobs),
            ],
            'budgets' => $budgets,
            'routes' => $routes,
            'slow_queries_top' => $slowQueries,
            'long_jobs_top' => $jobs,
            'recommendations' => $this->recommendations($budgets, $slowQueries, $jobs),
        ];
    }

    /**
     * @param array<string, mixed> $report
     */
    public function toMarkdown(array $report): string
    {
        $summary = (array) ($report['summary'] ?? []);
        $lines = [
            '# CATMIN Performance Report',
            '',
            '- Generated at: ' . (string) ($report['generated_at'] ?? now()->toIso8601String()),
            '- Window: ' . (int) ($report['window_hours'] ?? 24) . 'h',
            '- Requests profiled: ' . (int) ($summary['requests_profiled'] ?? 0),
            '- Slow requests: ' . (int) ($summary['slow_requests'] ?? 0),
            '- Budget breaches: ' . (int) ($summary['budget_breaches'] ?? 0),
            '- Slow queries: ' . (int) ($summary['slow_queries'] ?? 0),
            '- Long jobs: ' . (int) ($summary['long_jobs'] ?? 0),
            '',
            '## Budget Status',
            '',
        ];

        foreach ((array) ($report['budgets'] ?? []) as $budget) {
            $lines[] = '- ' . (string) ($budget['label'] ?? 'Budget')
                . ': avg=' . (int) ($budget['avg_response_ms'] ?? 0) . 'ms'
                . ', max=' . (int) ($budget['max_response_ms_seen'] ?? 0) . 'ms'
                . ', avg_queries=' . (int) ($budget['avg_queries'] ?? 0)
                . ', breaches=' . (int) ($budget['breaches'] ?? 0);
        }

        $lines[] = '';
        $lines[] = '## Recommendations';
        $lines[] = '';

        foreach ((array) ($report['recommendations'] ?? []) as $recommendation) {
            $lines[] = '- ' . (string) $recommendation;
        }

        return implode("\n", $lines) . "\n";
    }

    /**
     * @param array<string, mixed> $report
     * @return array{json:string, markdown:string}
     */
    public function saveReport(array $report): array
    {
        $directory = storage_path('app/reports');
        File::ensureDirectoryExists($directory);

        $timestamp = now()->format('Ymd-His');
        $jsonPath = $directory . '/performance-report-' . $timestamp . '.json';
        $markdownPath = $directory . '/performance-report-' . $timestamp . '.md';

        File::put($jsonPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        File::put($markdownPath, $this->toMarkdown($report));

        return [
            'json' => $jsonPath,
            'markdown' => $markdownPath,
        ];
    }

    /**
     * @param array<int, SystemLog> $logs
     * @return array<int, array<string, mixed>>
     */
    private function aggregateRoutes(array $logs): array
    {
        return collect($logs)
            ->groupBy(function (SystemLog $log): string {
                $context = (array) ($log->context ?? []);
                $route = trim((string) Arr::get($context, 'route_name', ''));
                $path = trim((string) Arr::get($context, 'path', ''));

                return $route !== '' ? $route : ($path !== '' ? $path : 'unknown');
            })
            ->map(function ($items, string $key): array {
                $rows = collect($items)->map(fn (SystemLog $log): array => (array) ($log->context ?? []));

                return [
                    'key' => $key,
                    'label' => $key,
                    'hits' => $rows->count(),
                    'avg_duration_ms' => (int) round($rows->avg(fn (array $row): int => (int) ($row['duration_ms'] ?? 0)) ?: 0),
                    'max_duration_ms' => (int) $rows->max(fn (array $row): int => (int) ($row['duration_ms'] ?? 0)),
                    'avg_queries' => (int) round($rows->avg(fn (array $row): int => (int) ($row['query_count'] ?? 0)) ?: 0),
                    'slow_requests' => $rows->filter(fn (array $row): bool => (bool) ($row['is_slow_request'] ?? false))->count(),
                    'budget_breaches' => $rows->filter(fn (array $row): bool => (bool) ($row['is_budget_breach'] ?? false))->count(),
                ];
            })
            ->sortByDesc(fn (array $row): array => [
                (int) ($row['budget_breaches'] ?? 0),
                (int) ($row['max_duration_ms'] ?? 0),
            ])
            ->take(10)
            ->values()
            ->all();
    }

    /**
     * @param array<int, SystemLog> $logs
     * @return array<int, array<string, mixed>>
     */
    private function aggregateBudgets(array $logs): array
    {
        $grouped = collect($logs)
            ->map(fn (SystemLog $log): array => (array) ($log->context ?? []))
            ->filter(fn (array $context): bool => is_array($context['budget'] ?? null))
            ->groupBy(fn (array $context): string => (string) Arr::get($context, 'budget.key', ''));

        return collect($this->budgetService->budgets())
            ->map(function (array $budget) use ($grouped): array {
                $rows = collect($grouped->get($budget['key'], []));

                return [
                    'key' => $budget['key'],
                    'label' => $budget['label'],
                    'category' => $budget['category'],
                    'hits' => $rows->count(),
                    'target_response_ms' => $budget['target_response_ms'],
                    'max_response_ms' => $budget['max_response_ms'],
                    'max_queries' => $budget['max_queries'],
                    'avg_response_ms' => (int) round($rows->avg(fn (array $row): int => (int) ($row['duration_ms'] ?? 0)) ?: 0),
                    'max_response_ms_seen' => (int) $rows->max(fn (array $row): int => (int) ($row['duration_ms'] ?? 0)),
                    'avg_queries' => (int) round($rows->avg(fn (array $row): int => (int) ($row['query_count'] ?? 0)) ?: 0),
                    'breaches' => $rows->filter(fn (array $row): bool => (bool) ($row['is_budget_breach'] ?? false))->count(),
                    'notes' => $budget['notes'],
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param array<int, SystemLog> $logs
     * @return array<int, array<string, mixed>>
     */
    private function aggregateSlowQueries(array $logs): array
    {
        return collect($logs)
            ->map(function (SystemLog $log): array {
                $context = (array) ($log->context ?? []);

                return [
                    'fingerprint' => sha1((string) Arr::get($context, 'sql', '')),
                    'sql' => (string) Arr::get($context, 'sql', ''),
                    'route_name' => (string) Arr::get($context, 'route_name', ''),
                    'path' => (string) Arr::get($context, 'path', ''),
                    'time_ms' => (int) Arr::get($context, 'time_ms', 0),
                ];
            })
            ->groupBy('fingerprint')
            ->map(function ($items): array {
                $first = $items->first();

                return [
                    'sql' => (string) ($first['sql'] ?? ''),
                    'hits' => $items->count(),
                    'max_time_ms' => (int) $items->max('time_ms'),
                    'avg_time_ms' => (int) round($items->avg('time_ms') ?: 0),
                    'route_name' => (string) ($first['route_name'] ?? ''),
                    'path' => (string) ($first['path'] ?? ''),
                ];
            })
            ->sortByDesc(fn (array $row): array => [(int) ($row['max_time_ms'] ?? 0), (int) ($row['hits'] ?? 0)])
            ->take(10)
            ->values()
            ->all();
    }

    /**
     * @param array<int, SystemLog> $logs
     * @return array<int, array<string, mixed>>
     */
    private function aggregateJobs(array $logs): array
    {
        return collect($logs)
            ->map(fn (SystemLog $log): array => (array) ($log->context ?? []))
            ->sortByDesc(fn (array $row): int => (int) ($row['duration_ms'] ?? 0))
            ->take(10)
            ->map(fn (array $row): array => [
                'job' => (string) ($row['job'] ?? 'queue.job'),
                'connection' => (string) ($row['connection'] ?? ''),
                'queue' => (string) ($row['queue'] ?? ''),
                'duration_ms' => (int) ($row['duration_ms'] ?? 0),
            ])
            ->values()
            ->all();
    }

    /**
     * @param array<int, array<string, mixed>> $budgets
     * @param array<int, array<string, mixed>> $slowQueries
     * @param array<int, array<string, mixed>> $jobs
     * @return array<int, string>
     */
    private function recommendations(array $budgets, array $slowQueries, array $jobs): array
    {
        $recommendations = [];

        foreach ($budgets as $budget) {
            if ((int) ($budget['breaches'] ?? 0) < 1) {
                continue;
            }

            if ((int) ($budget['avg_queries'] ?? 0) > (int) ($budget['max_queries'] ?? 0)) {
                $recommendations[] = 'Verifier mutualisation/caching sur ' . (string) ($budget['label'] ?? 'budget') . ' (volume de requetes au-dessus du budget).';
                continue;
            }

            $recommendations[] = 'Revoir la route ' . (string) ($budget['label'] ?? 'budget') . ' (temps de reponse au-dessus du budget).';
        }

        if ($slowQueries !== []) {
            $recommendations[] = 'Analyser les requetes lentes dominantes et ajouter eager loading, index ou simplification de KPI si necessaire.';
        }

        if ($jobs !== []) {
            $recommendations[] = 'Verifier les jobs les plus longs et deplacer les traitements lourds hors HTTP si ce n est pas deja le cas.';
        }

        if ($recommendations === []) {
            $recommendations[] = 'Aucune derive marquee sur la fenetre analysee. Continuer la surveillance des routes critiques et des widgets dashboard.';
        }

        return array_values(array_unique($recommendations));
    }
}