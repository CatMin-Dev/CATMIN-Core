<?php

use App\Services\CatminHookRegistry;

CatminHookRegistry::register('after:admin.topbar', static function (): string {
    if (!function_exists('catmin_can') || !catmin_can('addon.cat_wysiwyg.menu')) {
        return '';
    }

    if (!\Illuminate\Support\Facades\Route::has('admin.addon.cat_wysiwyg.index')) {
        return '';
    }

    $url = admin_route('addon.cat_wysiwyg.index');

    return '<div class="px-3 py-2 border-bottom bg-light small">'
        . '<a href="' . e($url) . '" class="text-decoration-none">'
        . '<i class="bi bi-pencil-square me-1"></i>Configurer CAT WYSIWYG'
        . '</a></div>';
});
