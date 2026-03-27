<?php

use Illuminate\Support\Facades\Route;
use Modules\Blocks\Controllers\Admin\BlockController;

Route::middleware(['web', 'catmin.admin'])
    ->prefix(config('catmin.admin.path', 'admin'))
    ->name('admin.')
    ->group(function (): void {
        Route::get('/blocks/manage', [BlockController::class, 'index'])
            ->middleware('catmin.permission:module.blocks.list')
            ->name('blocks.manage');

        Route::get('/blocks/create', [BlockController::class, 'create'])
            ->middleware('catmin.permission:module.blocks.create')
            ->name('blocks.create');

        Route::post('/blocks', [BlockController::class, 'store'])
            ->middleware('catmin.permission:module.blocks.create')
            ->name('blocks.store');

        Route::get('/blocks/{block}/edit', [BlockController::class, 'edit'])
            ->middleware('catmin.permission:module.blocks.edit')
            ->name('blocks.edit');

        Route::put('/blocks/{block}', [BlockController::class, 'update'])
            ->middleware('catmin.permission:module.blocks.edit')
            ->name('blocks.update');

        Route::patch('/blocks/{block}/toggle-status', [BlockController::class, 'toggleStatus'])
            ->middleware('catmin.permission:module.blocks.edit')
            ->name('blocks.toggle_status');
    });
