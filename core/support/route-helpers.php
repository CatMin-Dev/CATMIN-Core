<?php

declare(strict_types=1);

if (!function_exists('router_url')) {
    /** @param array<string, scalar> $params */
    function router_url(string $name, array $params = []): string
    {
        return Router::urlGenerator()->route($name, $params);
    }
}

if (!function_exists('admin_url')) {
    function admin_url(string $path = ''): string
    {
        return Router::urlGenerator()->admin($path);
    }
}

if (!function_exists('asset_url')) {
    function asset_url(string $path): string
    {
        return Router::urlGenerator()->asset($path);
    }
}
