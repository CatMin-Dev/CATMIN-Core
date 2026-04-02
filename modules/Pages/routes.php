<?php

use Illuminate\Support\Facades\Route;
use Modules\Pages\Controllers\Admin\PageController;

Route::middleware(['web', 'catmin.admin'])
    ->prefix(config('catmin.admin.path', 'admin'))
    ->name('admin.')
    ->group(function (): void {
        Route::get('/pages/manage', [PageController::class, 'index'])
            ->middleware('catmin.permission:module.pages.list')
            ->name('pages.manage');

        Route::get('/pages/create', [PageController::class, 'create'])
            ->middleware('catmin.permission:module.pages.create')
            ->name('pages.create');

        Route::post('/pages', [PageController::class, 'store'])
            ->middleware('catmin.permission:module.pages.create')
            ->name('pages.store');

        Route::delete('/pages/trash/empty', [PageController::class, 'emptyTrash'])
            ->middleware('catmin.permission:module.pages.trash')
            ->name('pages.trash.empty');

        Route::get('/pages/{page}/edit', [PageController::class, 'edit'])
            ->middleware('catmin.permission:module.pages.edit')
            ->name('pages.edit');

        Route::put('/pages/{page}', [PageController::class, 'update'])
            ->middleware('catmin.permission:module.pages.edit')
            ->name('pages.update');

        Route::match(['post', 'put'], '/pages/preview', [PageController::class, 'preview'])
            ->name('pages.preview');

        Route::patch('/pages/{page}/toggle-status', [PageController::class, 'toggleStatus'])
            ->middleware('catmin.permission:module.pages.edit')
            ->name('pages.toggle_status');

        Route::delete('/pages/{page}', [PageController::class, 'destroy'])
            ->middleware('catmin.permission:module.pages.trash')
            ->name('pages.destroy');

        Route::patch('/pages/{page}/restore', [PageController::class, 'restore'])
            ->middleware('catmin.permission:module.pages.trash')
            ->name('pages.restore');

        Route::delete('/pages/{page}/force-delete', [PageController::class, 'forceDelete'])
            ->middleware('catmin.permission:module.pages.trash')
            ->name('pages.force_delete');

        Route::post('/pages/bulk', [PageController::class, 'bulkAction'])
            ->middleware('catmin.permission:module.pages.list')
            ->name('pages.bulk');
    });
