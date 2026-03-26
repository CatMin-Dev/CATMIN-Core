<?php

namespace App\Services;

/**
 * AdminPathService
 *
 * Provides centralized access to admin routing paths and configurations.
 * This service ensures that admin paths are never hardcoded throughout the application,
 * making it easy to switch between /admin, /dashboard, or sub-domain routing.
 *
 * Usage:
 *   AdminPathService::login()      → /admin/login
 *   AdminPathService::dashboard()  → /admin/access
 *   AdminPathService::preview()    → /admin/preview
 *   AdminPathService::path()       → /admin
 */
class AdminPathService
{
    /**
     * Get the base admin path (prefix) configured in config/catmin.php
     *
     * @return string
     */
    public static function path(): string
    {
        return '/' . config('catmin.admin.path');
    }

    /**
     * Get login route
     *
     * @return string
     */
    public static function login(): string
    {
        return self::path() . '/login';
    }

    /**
     * Get logout route
     *
     * @return string
     */
    public static function logout(): string
    {
        return self::path() . '/logout';
    }

    /**
     * Get dashboard/access route
     *
     * @return string
     */
    public static function dashboard(): string
    {
        return self::path() . '/access';
    }

    /**
     * Get preview route for legacy pages
     *
     * @param string|null $page
     * @return string
     */
    public static function preview(?string $page = null): string
    {
        $base = self::path() . '/preview';
        return $page ? $base . '/' . $page : $base;
    }

    /**
     * Get error page route
     *
     * @param int $code HTTP error code (403, 404, 500)
     * @return string
     */
    public static function error(int $code): string
    {
        return self::path() . '/errors/' . $code;
    }

    /**
     * Get the complete admin config array
     *
     * @return array
     */
    public static function config(): array
    {
        return config('catmin.admin');
    }

    /**
     * Get admin route name prefix
     *
     * @return string
     */
    public static function routePrefix(): string
    {
        return config('catmin.admin.route_namespace') . '.';
    }

    /**
     * Check if admin authentication is enabled
     *
     * @return bool
     */
    public static function authEnabled(): bool
    {
        return config('catmin.features.admin_authentication_enabled', true);
    }

    /**
     * Get session key for admin authentication
     *
     * @return string
     */
    public static function sessionKey(): string
    {
        return config('catmin.admin.session_key');
    }

    /**
     * Generate a route name using the admin namespace
     *
     * @param string $name
     * @return string
     */
    public static function routeName(string $name): string
    {
        return self::routePrefix() . $name;
    }
}
