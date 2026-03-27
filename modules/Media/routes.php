<?php

use Illuminate\Support\Facades\Route;
use Modules\Media\Controllers\Admin\MediaController;

Route::middleware(['web', 'catmin.admin'])
    ->prefix(config('catmin.admin.path', 'admin'))
    ->name('admin.')
    ->group(function (): void {
        Route::get('/media/manage', [MediaController::class, 'index'])
            ->name('media.manage');

        Route::get('/media/create', [MediaController::class, 'create'])
            ->name('media.create');

        Route::post('/media', [MediaController::class, 'store'])
            ->name('media.store');

        Route::get('/media/{asset}/edit', [MediaController::class, 'edit'])
            ->name('media.edit');

        Route::put('/media/{asset}', [MediaController::class, 'update'])
            ->name('media.update');

        Route::delete('/media/{asset}', [MediaController::class, 'destroy'])
            ->name('media.destroy');
    });
