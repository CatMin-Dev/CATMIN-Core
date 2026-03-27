<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Modules\Logger\Services\SystemLogService;

class SettingService
{
    /**
     * Get all settings as a keyed collection.
     *
     * @return Collection<string, string|null>
     */
    public static function all(): Collection
    {
        $cacheKey = config('catmin.settings.cache_key', 'catmin.settings');

        $settings = Cache::rememberForever($cacheKey, function (): array {
            $defaults = collect(config('catmin.settings.defaults', []));
            $databaseSettings = Setting::query()->pluck('value', 'key');

            return $defaults->merge($databaseSettings)->all();
        });

        return collect($settings);
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::all()->get($key, $default);
    }

    public static function put(
        string $key,
        mixed $value,
        string $type = 'string',
        ?string $group = null,
        ?string $description = null,
        bool $isPublic = false
    ): Setting {
        $setting = Setting::updateOrCreate(
            ['key' => $key],
            [
                'value' => is_scalar($value) || $value === null ? $value : json_encode($value),
                'type' => $type,
                'group' => $group,
                'description' => $description,
                'is_public' => $isPublic,
            ]
        );

        self::forgetCache();

        CatminEventBus::dispatch(CatminEventBus::SETTING_UPDATED, [
            'setting' => [
                'key' => $setting->key,
                'value' => $setting->value,
                'type' => $setting->type,
                'group' => $setting->group,
                'is_public' => (bool) $setting->is_public,
            ],
        ]);

        try {
            /** @var SystemLogService $logger */
            $logger = app(SystemLogService::class);
            $logger->logAudit(
                'setting.updated',
                'Setting mise a jour',
                [
                    'key' => $setting->key,
                    'type' => $setting->type,
                    'group' => $setting->group,
                    'is_public' => (bool) $setting->is_public,
                ],
                'info',
                (string) session('catmin_admin_username', '')
            );
        } catch (\Throwable) {
            // Do not block setting updates if audit logging fails.
        }

        return $setting;
    }

    /**
     * @return Collection<string, string|null>
     */
    public static function group(string $group): Collection
    {
        return self::all()->filter(function ($value, $key) use ($group): bool {
            return str_starts_with($key, $group . '.');
        });
    }

    public static function forgetCache(): void
    {
        Cache::forget(config('catmin.settings.cache_key', 'catmin.settings'));
    }
}
