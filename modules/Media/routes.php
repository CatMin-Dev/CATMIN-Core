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

        Route::get('/media/picker', [MediaController::class, 'picker'])
            ->middleware('catmin.permission:module.media.list')
            ->name('media.picker');

        Route::get('/media/{asset}/picker-item', [MediaController::class, 'pickerItem'])
            ->middleware('catmin.permission:module.media.list')
            ->name('media.picker_item');

        Route::post('/media', [MediaController::class, 'store'])
            ->middleware('catmin.permission:module.media.create')
            ->name('media.store');

        Route::delete('/media/trash/empty', [MediaController::class, 'emptyTrash'])
            ->middleware('catmin.permission:module.media.trash')
            ->name('media.trash.empty');

        Route::get('/media/{asset}/edit', [MediaController::class, 'edit'])
            ->middleware('catmin.permission:module.media.edit')
            ->name('media.edit');

        Route::put('/media/{asset}', [MediaController::class, 'update'])
            ->middleware('catmin.permission:module.media.edit')
            ->name('media.update');

        Route::delete('/media/{asset}', [MediaController::class, 'destroy'])
            ->middleware('catmin.permission:module.media.trash')
            ->name('media.destroy');

        Route::patch('/media/{asset}/restore', [MediaController::class, 'restore'])
            ->middleware('catmin.permission:module.media.trash')
            ->name('media.restore');

        Route::delete('/media/{asset}/force-delete', [MediaController::class, 'forceDelete'])
            ->middleware('catmin.permission:module.media.trash')
            ->name('media.force_delete');

        Route::post('/media/bulk', [MediaController::class, 'bulkAction'])
            ->middleware('catmin.permission:module.media.list')
            ->name('media.bulk');
    });
