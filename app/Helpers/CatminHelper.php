<?php

use App\Services\AdminNavigationService;
use App\Services\SettingService;
use App\Services\ModuleManager;

if (!function_exists('setting')) {
    function setting(string $key, mixed $default = null): mixed
    {
        return SettingService::get($key, $default);
    }
}

if (!function_exists('module_enabled')) {
    function module_enabled(string $slug): bool
    {
        return ModuleManager::isEnabled($slug);
    }
}

if (!function_exists('module_info')) {
    function module_info(string $slug, ?string $property = null, mixed $default = null): mixed
    {
        $module = ModuleManager::find($slug);

        if (!$module) {
            return $default;
        }

        if ($property === null) {
            return $module;
        }

        return $module->{$property} ?? $default;
    }
}

if (!function_exists('admin_url')) {
    function admin_url(string $name, array $parameters = []): string
    {
        return admin_route($name, $parameters);
    }
}

if (!function_exists('catmin_navigation')) {
    function catmin_navigation(?string $currentPage = null): array
    {
        return AdminNavigationService::sections($currentPage);
    }
}

if (!function_exists('catmin_theme')) {
    function catmin_theme(string $default = 'catmin-light'): string
    {
        return (string) setting('admin.theme', $default);
    }
}
