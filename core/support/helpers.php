<?php

declare(strict_types=1);

use Core\config\Config;

if (!function_exists('base_path')) {
    function base_path(string $path = ''): string
    {
        return CATMIN_ROOT . ($path !== '' ? '/' . ltrim($path, '/') : '');
    }
}

if (!function_exists('storage_path')) {
    function storage_path(string $path = ''): string
    {
        return CATMIN_STORAGE . ($path !== '' ? '/' . ltrim($path, '/') : '');
    }
}

if (!function_exists('config')) {
    function config(string $key, mixed $default = null): mixed
    {
        return Config::get($key, $default);
    }
}
