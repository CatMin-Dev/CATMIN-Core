<?php

namespace Modules\Cache\Services;

use Illuminate\Support\Facades\Cache;

class QueryCacheService
{
    private const KEY_PREFIX = 'catmin.query.v2.';
    private const MODULES_KEY = self::KEY_PREFIX . 'modules';
    private const STATS_KEY = self::KEY_PREFIX . 'stats';

    /**
     * @template T
     * @param callable():T $resolver
     * @return T
     */
    public static function remember(string $module, string $key, int $ttlSeconds, callable $resolver): mixed
    {
        $safeModule = self::normalize($module);
        $safeKey = self::normalize($key);
        $cacheKey = self::cacheKey($safeModule, $safeKey);

        if (Cache::has($cacheKey)) {
            self::incrementStat('hits');

            /** @var T */
            return Cache::get($cacheKey);
        }

        self::incrementStat('misses');

        $value = $resolver();
        Cache::put($cacheKey, $value, now()->addSeconds(max(1, $ttlSeconds)));
        self::registerKey($safeModule, $cacheKey);

        return $value;
    }

    /** @param array<int, string> $modules */
    public static function invalidateModules(array $modules): int
    {
        $deleted = 0;

        foreach ($modules as $module) {
            $deleted += self::invalidateModule($module);
        }

        return $deleted;
    }

    public static function invalidateModule(string $module): int
    {
        $safeModule = self::normalize($module);
        $registryKey = self::moduleRegistryKey($safeModule);
        $keys = (array) Cache::get($registryKey, []);
        $deleted = 0;

        foreach ($keys as $cacheKey) {
            if (is_string($cacheKey) && Cache::has($cacheKey)) {
                Cache::forget($cacheKey);
                $deleted++;
            }
        }

        Cache::forget($registryKey);

        $modules = array_values(array_filter((array) Cache::get(self::MODULES_KEY, []), fn ($name): bool => $name !== $safeModule));
        Cache::forever(self::MODULES_KEY, $modules);

        self::incrementStat('invalidations', $deleted > 0 ? $deleted : 1);

        return $deleted;
    }

    public static function flushAll(): int
    {
        $modules = (array) Cache::get(self::MODULES_KEY, []);
        $deleted = 0;

        foreach ($modules as $module) {
            if (is_string($module) && $module !== '') {
                $deleted += self::invalidateModule($module);
            }
        }

        Cache::forget(self::MODULES_KEY);

        return $deleted;
    }

    /**
     * @return array<string, mixed>
     */
    public static function stats(): array
    {
        $stats = (array) Cache::get(self::STATS_KEY, []);
        $hits = (int) ($stats['hits'] ?? 0);
        $misses = (int) ($stats['misses'] ?? 0);
        $invalidations = (int) ($stats['invalidations'] ?? 0);
        $requests = $hits + $misses;
        $modules = (array) Cache::get(self::MODULES_KEY, []);

        $moduleStats = [];
        $keysCount = 0;

        foreach ($modules as $module) {
            if (!is_string($module) || $module === '') {
                continue;
            }

            $count = count((array) Cache::get(self::moduleRegistryKey($module), []));
            $moduleStats[] = ['module' => $module, 'keys' => $count];
            $keysCount += $count;
        }

        usort($moduleStats, static fn (array $a, array $b): int => (int) $b['keys'] <=> (int) $a['keys']);

        return [
            'hits' => $hits,
            'misses' => $misses,
            'requests' => $requests,
            'hit_ratio' => $requests > 0 ? round(($hits / $requests) * 100, 1) : 0.0,
            'invalidations' => $invalidations,
            'modules' => count($moduleStats),
            'keys' => $keysCount,
            'top_modules' => array_slice($moduleStats, 0, 8),
        ];
    }

    private static function cacheKey(string $module, string $key): string
    {
        return self::KEY_PREFIX . $module . '.' . $key;
    }

    private static function moduleRegistryKey(string $module): string
    {
        return self::KEY_PREFIX . 'registry.' . $module;
    }

    private static function registerKey(string $module, string $cacheKey): void
    {
        $registryKey = self::moduleRegistryKey($module);
        $keys = (array) Cache::get($registryKey, []);

        if (!in_array($cacheKey, $keys, true)) {
            $keys[] = $cacheKey;
            Cache::forever($registryKey, array_values($keys));
        }

        $modules = (array) Cache::get(self::MODULES_KEY, []);
        if (!in_array($module, $modules, true)) {
            $modules[] = $module;
            Cache::forever(self::MODULES_KEY, array_values($modules));
        }
    }

    private static function incrementStat(string $name, int $by = 1): void
    {
        $stats = (array) Cache::get(self::STATS_KEY, []);
        $stats[$name] = ((int) ($stats[$name] ?? 0)) + $by;
        Cache::forever(self::STATS_KEY, $stats);
    }

    private static function normalize(string $value): string
    {
        return trim(preg_replace('/[^a-z0-9_\-\.]/i', '_', strtolower($value)) ?? '');
    }
}
