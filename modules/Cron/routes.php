<?php

use Illuminate\Support\Facades\Route;
use Modules\Cron\Controllers\Admin\CronController;

Route::middleware(['web', 'catmin.admin'])
    ->prefix(config('catmin.admin.path', 'admin'))
    ->name('admin.')
    ->group(function (): void {
        Route::get('/cron', [CronController::class, 'index'])->name('cron.index');
        Route::post('/cron/run/{task}', [CronController::class, 'runTask'])->name('cron.run');
    });
