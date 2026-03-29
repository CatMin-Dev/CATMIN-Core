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
        Route::get('/queue/jobs/{source}/{id}', [QueueController::class, 'showJob'])
            ->whereIn('source', ['failed', 'pending'])
            ->middleware('catmin.permission:module.queue.list')
            ->name('queue.job.show');
        Route::post('/queue/failed/{id}/retry', [QueueController::class, 'retryFailedJob'])
            ->middleware('catmin.permission:module.queue.config')
            ->name('queue.failed.retry');
        Route::post('/queue/failed/retry-selected', [QueueController::class, 'retrySelectedFailed'])
            ->middleware('catmin.permission:module.queue.config')
            ->name('queue.failed.retry-selected');
        Route::post('/queue/failed/retry-all', [QueueController::class, 'retryAllFailed'])
            ->middleware('catmin.permission:module.queue.config')
            ->name('queue.failed.retry-all');
        Route::delete('/queue/failed/{id}', [QueueController::class, 'deleteFailedJob'])
            ->middleware('catmin.permission:module.queue.config')
            ->name('queue.failed.delete');
        Route::post('/queue/failed/selected', [QueueController::class, 'clearSelectedFailed'])
            ->middleware('catmin.permission:module.queue.config')
            ->name('queue.failed.clear-selected');
        Route::delete('/queue/failed', [QueueController::class, 'clearFailed'])
            ->middleware('catmin.permission:module.queue.config')
            ->name('queue.failed.clear');
    });
