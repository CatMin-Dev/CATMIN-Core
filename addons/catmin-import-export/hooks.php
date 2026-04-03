<?php

use App\Services\CatminHookRegistry;

CatminHookRegistry::register('after:admin.topbar', static function (): string {
    if (!function_exists('catmin_can') || !catmin_can('module.import_export.menu')) {
        return '';
    }

    if (!\Illuminate\Support\Facades\Route::has('admin.import_export.index')) {
        return '';
    }

    return '<div class="px-3 py-2 border-bottom bg-light small">'
        . '<a href="' . e(admin_route('import_export.index')) . '" class="text-decoration-none">'
        . '<i class="bi bi-arrow-left-right me-1"></i>Import / Export'
        . '</a></div>';
});