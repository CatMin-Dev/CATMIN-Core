<?php

declare(strict_types=1);

namespace Modules\Core\Services;

use App\Services\ModuleManager;
use App\Services\SettingService;

final class CoreFoundationService
{
    /**
     * Kernel-level information used by admin diagnostics and module consumers.
     *
     * @return array<string, mixed>
     */
    public static function status(): array
    {
        return [
            'app' => [
                'name' => (string) config('app.name', 'CATMIN'),
                'env' => (string) config('app.env', 'production'),
                'url' => (string) config('app.url', ''),
            ],
            'settings' => [
                'site.name' => SettingService::get('site.name', 'CATMIN'),
                'site.url' => SettingService::get('site.url', config('app.url')),
                'admin.path' => config('catmin.admin.path', 'admin'),
            ],
            'modules' => [
                'total' => ModuleManager::count(),
                'enabled' => ModuleManager::enabledCount(),
                'summary' => ModuleManager::summary(),
            ],
        ];
    }
}
