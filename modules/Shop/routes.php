<?php

use Illuminate\Support\Facades\Route;
use Modules\Shop\Controllers\Admin\ProductController;

Route::middleware(['web', 'catmin.admin'])
    ->prefix(config('catmin.admin.path', 'admin'))
    ->name('admin.')
    ->group(function (): void {
        Route::get('/shop/manage', [ProductController::class, 'index'])
            ->middleware('catmin.permission:module.shop.list')
            ->name('shop.manage');

        Route::get('/shop/create', [ProductController::class, 'create'])
            ->middleware('catmin.permission:module.shop.create')
            ->name('shop.create');

        Route::post('/shop', [ProductController::class, 'store'])
            ->middleware('catmin.permission:module.shop.create')
            ->name('shop.store');

        Route::get('/shop/{product}/edit', [ProductController::class, 'edit'])
            ->middleware('catmin.permission:module.shop.edit')
            ->name('shop.edit');

        Route::put('/shop/{product}', [ProductController::class, 'update'])
            ->middleware('catmin.permission:module.shop.edit')
            ->name('shop.update');

        Route::patch('/shop/{product}/toggle-status', [ProductController::class, 'toggleStatus'])
            ->middleware('catmin.permission:module.shop.edit')
            ->name('shop.toggle_status');
    });
