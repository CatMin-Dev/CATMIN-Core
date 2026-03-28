<?php

use Illuminate\Support\Facades\Route;
use Modules\Logger\Controllers\Admin\AlertController;
use Modules\Logger\Controllers\Admin\LogController;

Route::middleware(['web', 'catmin.admin'])
    ->prefix(config('catmin.admin.path', 'admin'))
    ->name('admin.')
    ->group(function (): void {
        Route::get('/logs', [LogController::class, 'index'])
            ->middleware('catmin.permission:module.logger.list')
            ->name('logger.index');

        Route::get('/logs/alerts', [AlertController::class, 'index'])
            ->middleware('catmin.permission:module.logger.list')
            ->name('logger.alerts.index');

        Route::post('/logs/alerts/acknowledge', [AlertController::class, 'acknowledge'])
            ->middleware('catmin.permission:module.logger.list')
            ->name('logger.alerts.acknowledge');
    });
