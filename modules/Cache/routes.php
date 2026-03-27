<?php

use Illuminate\Support\Facades\Route;
use Modules\Cache\Controllers\Admin\CacheController;

Route::middleware(['web', 'catmin.admin'])
    ->prefix(config('catmin.admin.path', 'admin'))
    ->name('admin.')
    ->group(function (): void {
        Route::get('/cache', [CacheController::class, 'index'])
            ->name('cache.index');

        Route::post('/cache/clear', [CacheController::class, 'clearAll'])
            ->name('cache.clear');

        Route::post('/cache/clear/settings', [CacheController::class, 'clearSettings'])
            ->name('cache.clear.settings');

        Route::post('/cache/clear/views', [CacheController::class, 'clearViews'])
            ->name('cache.clear.views');
    });
