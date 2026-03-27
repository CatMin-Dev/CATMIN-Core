<?php

use Illuminate\Support\Facades\Route;
use Modules\Cache\Controllers\Admin\CacheController;

Route::middleware(['web', 'catmin.admin'])
    ->prefix(config('catmin.admin.path', 'admin'))
    ->name('admin.')
    ->group(function (): void {
        Route::get('/cache', [CacheController::class, 'index'])
            ->middleware('catmin.permission:module.cache.list')
            ->name('cache.index');

        Route::post('/cache/clear', [CacheController::class, 'clearAll'])
            ->middleware('catmin.permission:module.cache.config')
            ->name('cache.clear');

        Route::post('/cache/clear/settings', [CacheController::class, 'clearSettings'])
            ->middleware('catmin.permission:module.cache.config')
            ->name('cache.clear.settings');

        Route::post('/cache/clear/views', [CacheController::class, 'clearViews'])
            ->middleware('catmin.permission:module.cache.config')
            ->name('cache.clear.views');
    });
