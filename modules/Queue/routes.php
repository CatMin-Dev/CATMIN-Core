<?php

use Illuminate\Support\Facades\Route;
use Modules\Queue\Controllers\Admin\QueueController;

Route::middleware(['web', 'catmin.admin'])
    ->prefix(config('catmin.admin.path', 'admin'))
    ->name('admin.')
    ->group(function (): void {
        Route::get('/queue', [QueueController::class, 'index'])
            ->middleware('catmin.permission:module.queue.list')
            ->name('queue.index');
        Route::delete('/queue/failed/{id}', [QueueController::class, 'deleteFailedJob'])
            ->middleware('catmin.permission:module.queue.config')
            ->name('queue.failed.delete');
        Route::delete('/queue/failed', [QueueController::class, 'clearFailed'])
            ->middleware('catmin.permission:module.queue.config')
            ->name('queue.failed.clear');
    });
