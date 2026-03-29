<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\CatminEventBus;
use App\Services\Performance\PerformanceBudgetService;
use App\Services\Performance\RequestPerformanceState;
use Modules\Logger\Services\SystemLogService;
use Symfony\Component\HttpFoundation\Response;

final class LogRequestPerformance
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$this->shouldProfile($request)) {
            return $next($request);
        }

        $startedAt = microtime(true);
        $memoryStart = memory_get_usage(true);
        RequestPerformanceState::reset();

        /** @var Response $response */
        $response = $next($request);

        $durationMs = (int) round((microtime(true) - $startedAt) * 1000);
        $memoryPeak = max(0, memory_get_peak_usage(true) - $memoryStart);
        $threshold = (int) config('catmin.performance.slow_request_ms', 800);
        $routeName = (string) optional($request->route())->getName();
        $metrics = RequestPerformanceState::snapshot();
        $budget = app(PerformanceBudgetService::class)->budgetForRequest($request);
        $evaluation = $budget !== null ? app(PerformanceBudgetService::class)->evaluate($budget, [
            'duration_ms' => $durationMs,
            'query_count' => $metrics['query_count'] ?? 0,
            'slow_query_count' => $metrics['slow_query_count'] ?? 0,
        ]) : null;
        $isSlowRequest = $durationMs >= $threshold;
        $isBudgetBreach = (bool) ($evaluation['is_breach'] ?? false);

        try {
            app(SystemLogService::class)->logPerformance(
                'http.request.performance',
                'HTTP request performance',
                [
                    'route_name' => $routeName,
                    'path' => (string) $request->path(),
                    'method' => (string) $request->method(),
                    'status_code' => $response->getStatusCode(),
                    'duration_ms' => $durationMs,
                    'memory_peak_bytes' => $memoryPeak,
                    'query_count' => (int) ($metrics['query_count'] ?? 0),
                    'slow_query_count' => (int) ($metrics['slow_query_count'] ?? 0),
                    'total_query_time_ms' => (int) ($metrics['total_query_time_ms'] ?? 0),
                    'slow_queries' => (array) ($metrics['slow_queries'] ?? []),
                    'ip' => (string) $request->ip(),
                    'slow_threshold_ms' => $threshold,
                    'is_slow_request' => $isSlowRequest,
                    'is_budget_breach' => $isBudgetBreach,
                    'budget' => $budget,
                    'budget_breaches' => (array) ($evaluation['breaches'] ?? []),
                ],
                ($isSlowRequest || $isBudgetBreach) ? 'warning' : 'info'
            );

            if ($isBudgetBreach) {
                CatminEventBus::dispatch('monitoring.performance.threshold_exceeded', [
                    'route_name' => $routeName,
                    'path' => (string) $request->path(),
                    'duration_ms' => $durationMs,
                    'query_count' => (int) ($metrics['query_count'] ?? 0),
                    'breaches' => (array) ($evaluation['breaches'] ?? []),
                    'budget' => $budget,
                ]);
            }
        } catch (\Throwable) {
            // Keep request flow resilient if perf logging fails.
        }

        return $response;
    }

    private function shouldProfile(Request $request): bool
    {
        $path = ltrim((string) $request->path(), '/');
        $adminPrefix = trim((string) config('catmin.admin.path', 'admin'), '/');

        if ($adminPrefix !== '' && ($path === $adminPrefix || str_starts_with($path, $adminPrefix . '/'))) {
            return true;
        }

        return str_starts_with($path, 'api/v1/') || str_starts_with($path, 'api/v2/');
    }
}
