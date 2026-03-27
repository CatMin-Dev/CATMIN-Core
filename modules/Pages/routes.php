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

        Route::get('/pages/{page}/edit', [PageController::class, 'edit'])
            ->middleware('catmin.permission:module.pages.edit')
            ->name('pages.edit');

        Route::put('/pages/{page}', [PageController::class, 'update'])
            ->middleware('catmin.permission:module.pages.edit')
            ->name('pages.update');

        Route::patch('/pages/{page}/toggle-status', [PageController::class, 'toggleStatus'])
            ->middleware('catmin.permission:module.pages.edit')
            ->name('pages.toggle_status');
    });
