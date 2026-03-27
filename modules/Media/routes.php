<?php

use Illuminate\Support\Facades\Route;
use Modules\Media\Controllers\Admin\MediaController;

Route::middleware(['web', 'catmin.admin'])
    ->prefix(config('catmin.admin.path', 'admin'))
    ->name('admin.')
    ->group(function (): void {
        Route::get('/media/manage', [MediaController::class, 'index'])
            ->middleware('catmin.permission:module.media.list')
            ->name('media.manage');

        Route::get('/media/create', [MediaController::class, 'create'])
            ->middleware('catmin.permission:module.media.create')
            ->name('media.create');

        Route::post('/media', [MediaController::class, 'store'])
            ->middleware('catmin.permission:module.media.create')
            ->name('media.store');

        Route::get('/media/{asset}/edit', [MediaController::class, 'edit'])
            ->middleware('catmin.permission:module.media.edit')
            ->name('media.edit');

        Route::put('/media/{asset}', [MediaController::class, 'update'])
            ->middleware('catmin.permission:module.media.edit')
            ->name('media.update');

        Route::delete('/media/{asset}', [MediaController::class, 'destroy'])
            ->middleware('catmin.permission:module.media.delete')
            ->name('media.destroy');
    });
