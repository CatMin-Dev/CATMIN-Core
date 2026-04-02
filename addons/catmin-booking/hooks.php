<?php

use App\Services\CatminHookRegistry;

CatminHookRegistry::register('after:admin.topbar', static function (): string {
    if (!function_exists('catmin_can') || !catmin_can('module.booking.menu')) {
        return '';
    }

    if (!\Illuminate\Support\Facades\Route::has('admin.booking.services.index')) {
        return '';
    }

    $url = admin_route('booking.services.index');

    return '<div class="px-3 py-2 border-bottom bg-light small">'
        . '<a href="' . e($url) . '" class="text-decoration-none">'
        . '<i class="bi bi-calendar-check me-1"></i>Booking'
        . '</a></div>';
});