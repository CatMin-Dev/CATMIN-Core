<?php

use Illuminate\Support\Facades\Route;
use Modules\Cron\Controllers\Admin\CronController;

Route::middleware(['web', 'catmin.admin'])
    ->prefix(config('catmin.admin.path', 'admin'))
    ->name('admin.')
    ->group(function (): void {
        Route::get('/cron', [CronController::class, 'index'])
            ->middleware('catmin.permission:module.cron.list')
            ->name('cron.index');
        Route::post('/cron/run/{task}', [CronController::class, 'runTask'])
            ->middleware('catmin.permission:module.cron.config')
            ->name('cron.run');
        Route::post('/cron/custom', [CronController::class, 'storeCustomTask'])
            ->middleware('catmin.permission:module.cron.config')
            ->name('cron.custom.store');
        Route::delete('/cron/custom/{taskId}', [CronController::class, 'deleteCustomTask'])
            ->middleware('catmin.permission:module.cron.config')
            ->name('cron.custom.delete');
    });
