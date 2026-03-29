<?php

namespace App\Services\Performance;

class JobPerformanceState
{
    /**
     * @var array<string, float>
     */
    protected static array $startedAt = [];

    public static function start(string $key): void
    {
        self::$startedAt[$key] = microtime(true);
    }

    public static function stop(string $key): ?int
    {
        if (!isset(self::$startedAt[$key])) {
            return null;
        }

        $startedAt = self::$startedAt[$key];
        unset(self::$startedAt[$key]);

        return (int) round((microtime(true) - $startedAt) * 1000);
    }
}