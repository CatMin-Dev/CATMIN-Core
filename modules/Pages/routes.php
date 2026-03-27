<?php

use Illuminate\Support\Facades\Route;
use Modules\Pages\Controllers\Admin\PageController;

Route::middleware(['web', 'catmin.admin'])
    ->prefix(config('catmin.admin.path', 'admin'))
    ->name('admin.')
    ->group(function (): void {
        Route::get('/pages/manage', [PageController::class, 'index'])
            ->name('pages.manage');

        Route::get('/pages/create', [PageController::class, 'create'])
            ->name('pages.create');

        Route::post('/pages', [PageController::class, 'store'])
            ->name('pages.store');

        Route::get('/pages/{page}/edit', [PageController::class, 'edit'])
            ->name('pages.edit');

        Route::put('/pages/{page}', [PageController::class, 'update'])
            ->name('pages.update');

        Route::patch('/pages/{page}/toggle-status', [PageController::class, 'toggleStatus'])
            ->name('pages.toggle_status');
    });
