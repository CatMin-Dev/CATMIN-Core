<?php

use Illuminate\Support\Facades\Route;
use Modules\Logger\Controllers\Admin\AlertController;
use Modules\Logger\Controllers\Admin\LogController;
use Modules\Logger\Controllers\Admin\MonitoringController;
use Modules\Logger\Controllers\Admin\PerformanceController;

Route::middleware(['web', 'catmin.admin'])
    ->prefix(config('catmin.admin.path', 'admin'))
    ->name('admin.')
    ->group(function (): void {
        Route::get('/logs', [LogController::class, 'index'])
            ->middleware('catmin.permission:module.logger.list')
            ->name('logger.index');

        Route::post('/logs/purge', [LogController::class, 'purge'])
            ->middleware('catmin.permission:module.logger.list')
            ->name('logger.purge');

        Route::get('/logs/alerts', [AlertController::class, 'index'])
            ->middleware('catmin.permission:module.logger.list')
            ->name('logger.alerts.index');

        Route::post('/logs/alerts/acknowledge', [AlertController::class, 'acknowledge'])
            ->middleware('catmin.permission:module.logger.list')
            ->name('logger.alerts.acknowledge');

        Route::get('/monitoring', [MonitoringController::class, 'index'])
            ->middleware('catmin.permission:module.logger.list')
            ->name('monitoring.index');

        Route::get('/monitoring/incidents', [MonitoringController::class, 'incidents'])
            ->middleware('catmin.permission:module.logger.list')
            ->name('monitoring.incidents');

        Route::post('/monitoring/snapshot', [MonitoringController::class, 'snapshot'])
            ->middleware('catmin.permission:module.logger.list')
            ->name('monitoring.snapshot');

        Route::get('/performance', [PerformanceController::class, 'index'])
            ->middleware('catmin.permission:module.logger.list')
            ->name('performance.index');
    });
