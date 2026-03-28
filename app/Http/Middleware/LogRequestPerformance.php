<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Logger\Services\SystemLogService;
use Symfony\Component\HttpFoundation\Response;

final class LogRequestPerformance
{
    public function handle(Request $request, Closure $next): Response
    {
        $startedAt = microtime(true);
        $memoryStart = memory_get_usage(true);

        /** @var Response $response */
        $response = $next($request);

        $durationMs = (int) round((microtime(true) - $startedAt) * 1000);
        $memoryPeak = max(0, memory_get_peak_usage(true) - $memoryStart);
        $threshold = (int) config('catmin.performance.slow_request_ms', 800);

        try {
            app(SystemLogService::class)->logPerformance(
                'http.request.performance',
                'HTTP request performance',
                [
                    'path' => (string) $request->path(),
                    'method' => (string) $request->method(),
                    'status_code' => $response->getStatusCode(),
                    'duration_ms' => $durationMs,
                    'memory_peak_bytes' => $memoryPeak,
                    'ip' => (string) $request->ip(),
                    'slow_threshold_ms' => $threshold,
                ],
                $durationMs >= $threshold ? 'warning' : 'info'
            );
        } catch (\Throwable) {
            // Keep request flow resilient if perf logging fails.
        }

        return $response;
    }
}
