<?php

use Illuminate\Support\Facades\Route;
use Modules\Shop\Controllers\Admin\ProductController;

Route::middleware(['web', 'catmin.admin'])
    ->prefix(config('catmin.admin.path', 'admin'))
    ->name('admin.')
    ->group(function (): void {
        Route::get('/shop/manage', [ProductController::class, 'index'])
            ->name('shop.manage');

        Route::get('/shop/create', [ProductController::class, 'create'])
            ->name('shop.create');

        Route::post('/shop', [ProductController::class, 'store'])
            ->name('shop.store');

        Route::get('/shop/{product}/edit', [ProductController::class, 'edit'])
            ->name('shop.edit');

        Route::put('/shop/{product}', [ProductController::class, 'update'])
            ->name('shop.update');

        Route::patch('/shop/{product}/toggle-status', [ProductController::class, 'toggleStatus'])
            ->name('shop.toggle_status');
    });
