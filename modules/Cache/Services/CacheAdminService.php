<?php

namespace Modules\Cache\Services;

use App\Services\SettingService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

class CacheAdminService
{
    /**
     * @return array{driver: string, prefix: string, store: string}
     */
    public static function info(): array
    {
        return [
            'driver' => config('cache.default', 'file'),
            'prefix' => config('cache.prefix', ''),
            'store' => config('cache.stores.' . config('cache.default') . '.driver', 'unknown'),
        ];
    }

    /**
     * Clear everything: application cache, views, config.
     *
     * @return array<string, bool>
     */
    public static function clearAll(): array
    {
        $results = [];

        try {
            Artisan::call('cache:clear');
            $results['application'] = true;
        } catch (\Throwable) {
            $results['application'] = false;
        }

        try {
            SettingService::forgetCache();
            $results['settings'] = true;
        } catch (\Throwable) {
            $results['settings'] = false;
        }

        try {
            Artisan::call('view:clear');
            $results['views'] = true;
        } catch (\Throwable) {
            $results['views'] = false;
        }

        try {
            Artisan::call('config:clear');
            $results['config'] = true;
        } catch (\Throwable) {
            $results['config'] = false;
        }

        return $results;
    }

    public static function clearSettings(): void
    {
        SettingService::forgetCache();
    }

    public static function clearViews(): void
    {
        Artisan::call('view:clear');
    }

    /**
     * Estimate cache entry count (database store only).
     */
    public static function cacheEntryCount(): int
    {
        try {
            if (config('cache.default') === 'database') {
                return (int) \Illuminate\Support\Facades\DB::table('cache')->count();
            }
        } catch (\Throwable) {
            // ignore
        }

        return -1;
    }
}
