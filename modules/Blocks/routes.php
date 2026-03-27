<?php

use Illuminate\Support\Facades\Route;
use Modules\Blocks\Controllers\Admin\BlockController;

Route::middleware(['web', 'catmin.admin'])
    ->prefix(config('catmin.admin.path', 'admin'))
    ->name('admin.')
    ->group(function (): void {
        Route::get('/blocks/manage', [BlockController::class, 'index'])
            ->name('blocks.manage');

        Route::get('/blocks/create', [BlockController::class, 'create'])
            ->name('blocks.create');

        Route::post('/blocks', [BlockController::class, 'store'])
            ->name('blocks.store');

        Route::get('/blocks/{block}/edit', [BlockController::class, 'edit'])
            ->name('blocks.edit');

        Route::put('/blocks/{block}', [BlockController::class, 'update'])
            ->name('blocks.update');

        Route::patch('/blocks/{block}/toggle-status', [BlockController::class, 'toggleStatus'])
            ->name('blocks.toggle_status');
    });
