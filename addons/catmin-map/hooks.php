<?php

use App\Services\CatminHookRegistry;

CatminHookRegistry::register('after:admin.topbar', static function (): string {
    if (!function_exists('catmin_can') || !catmin_can('module.map.menu')) {
        return '';
    }

    if (!\Illuminate\Support\Facades\Route::has('admin.map.index')) {
        return '';
    }

    $url = admin_route('map.index');

    return '<div class="px-3 py-2 border-bottom bg-light small">'
        . '<a href="' . e($url) . '" class="text-decoration-none">'
        . '<i class="bi bi-map me-1"></i>Carte'
        . '</a></div>';
});
