<?php

use Illuminate\Support\Facades\Route;
use Modules\Queue\Controllers\Admin\QueueController;

Route::middleware(['web', 'catmin.admin'])
    ->prefix(config('catmin.admin.path', 'admin'))
    ->name('admin.')
    ->group(function (): void {
        Route::get('/queue', [QueueController::class, 'index'])->name('queue.index');
        Route::delete('/queue/failed/{id}', [QueueController::class, 'deleteFailedJob'])
            ->name('queue.failed.delete');
        Route::delete('/queue/failed', [QueueController::class, 'clearFailed'])
            ->name('queue.failed.clear');
    });
