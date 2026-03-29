<?php

namespace App\Services\Performance;

use Illuminate\Support\Str;

class RequestPerformanceState
{
    protected static int $queryCount = 0;

    protected static int $slowQueryCount = 0;

    protected static float $totalQueryTimeMs = 0.0;

    /**
     * @var array<int, array<string, mixed>>
     */
    protected static array $slowQueries = [];

    public static function reset(): void
    {
        self::$queryCount = 0;
        self::$slowQueryCount = 0;
        self::$totalQueryTimeMs = 0.0;
        self::$slowQueries = [];
    }

    public static function recordQuery(string $sql, float $timeMs, string $connection, float $slowThresholdMs): void
    {
        self::$queryCount++;
        self::$totalQueryTimeMs += $timeMs;

        if ($timeMs < $slowThresholdMs) {
            return;
        }

        self::$slowQueryCount++;
        self::$slowQueries[] = [
            'sql' => Str::limit(preg_replace('/\s+/', ' ', trim($sql)) ?: $sql, 220, '...'),
            'time_ms' => (int) round($timeMs),
            'connection' => $connection,
        ];

        usort(self::$slowQueries, fn (array $left, array $right): int => (int) (($right['time_ms'] ?? 0) <=> ($left['time_ms'] ?? 0)));
        self::$slowQueries = array_slice(self::$slowQueries, 0, 5);
    }

    /**
     * @return array<string, mixed>
     */
    public static function snapshot(): array
    {
        return [
            'query_count' => self::$queryCount,
            'slow_query_count' => self::$slowQueryCount,
            'total_query_time_ms' => (int) round(self::$totalQueryTimeMs),
            'slow_queries' => array_values(self::$slowQueries),
        ];
    }
}