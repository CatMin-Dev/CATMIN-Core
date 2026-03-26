<?php

use App\Services\AdminPathService;

if (!function_exists('admin_path')) {
    /**
     * Get admin path or specific admin route
     *
     * @param string|null $path Optional path to append (login, dashboard, etc)
     * @return string
     *
     * @example
     *   admin_path()               // → /admin
     *   admin_path('login')        // → /admin/login
     *   admin_path('preview/forms') // → /admin/preview/forms
     */
    function admin_path(?string $path = null): string
    {
        return AdminPathService::path() . ($path ? '/' . ltrim($path, '/') : '');
    }
}

if (!function_exists('admin_route')) {
    /**
     * Get admin route URL by name
     *
     * @param string $name Route name (without admin. prefix)
     * @param mixed $parameters Route parameters
     * @return string
     *
     * @example
     *   admin_route('login')       // → /admin/login
     *   admin_route('preview', ['page' => 'dashboard'])
     */
    function admin_route(string $name, $parameters = []): string
    {
        return route(AdminPathService::routeName($name), $parameters);
    }
}
