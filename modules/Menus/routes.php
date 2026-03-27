<?php

use Illuminate\Support\Facades\Route;
use Modules\Menus\Controllers\Admin\MenuController;

Route::middleware(['web', 'catmin.admin'])
    ->prefix(config('catmin.admin.path', 'admin'))
    ->name('admin.')
    ->group(function (): void {
        Route::get('/menus/manage', [MenuController::class, 'index'])
            ->name('menus.manage');

        Route::get('/menus/create', [MenuController::class, 'create'])
            ->name('menus.create');

        Route::post('/menus', [MenuController::class, 'store'])
            ->name('menus.store');

        Route::get('/menus/{menu}/edit', [MenuController::class, 'edit'])
            ->name('menus.edit');

        Route::put('/menus/{menu}', [MenuController::class, 'update'])
            ->name('menus.update');

        Route::patch('/menus/{menu}/toggle-status', [MenuController::class, 'toggleStatus'])
            ->name('menus.toggle_status');

        Route::post('/menus/{menu}/items', [MenuController::class, 'storeItem'])
            ->name('menus.items.store');

        Route::patch('/menus/{menu}/items/{item}/toggle-status', [MenuController::class, 'toggleItemStatus'])
            ->name('menus.items.toggle_status');
    });
