<?php

use Illuminate\Support\Facades\Route;
use Modules\Logger\Controllers\Admin\LogController;

Route::middleware(['web', 'catmin.admin'])
    ->prefix(config('catmin.admin.path', 'admin'))
    ->name('admin.')
    ->group(function (): void {
        Route::get('/logs', [LogController::class, 'index'])
            ->middleware('catmin.permission:module.logger.list')
            ->name('logger.index');
    });
