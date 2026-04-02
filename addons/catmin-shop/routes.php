<?php

use Illuminate\Support\Facades\Route;
use Addons\CatminShop\Controllers\Admin\CategoryController;
use Addons\CatminShop\Controllers\Admin\CustomerController;
use Addons\CatminShop\Controllers\Admin\InvoiceController;
use Addons\CatminShop\Controllers\Admin\OrderController;
use Addons\CatminShop\Controllers\Admin\ProductController;

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

        Route::get('/shop/categories', [CategoryController::class, 'index'])
            ->middleware('catmin.permission:module.shop.list')
            ->name('shop.categories.index');

        Route::post('/shop/categories', [CategoryController::class, 'store'])
            ->middleware('catmin.permission:module.shop.create')
            ->name('shop.categories.store');

        Route::get('/shop/categories/{category}/edit', [CategoryController::class, 'edit'])
            ->middleware('catmin.permission:module.shop.edit')
            ->name('shop.categories.edit');

        Route::put('/shop/categories/{category}', [CategoryController::class, 'update'])
            ->middleware('catmin.permission:module.shop.edit')
            ->name('shop.categories.update');

        Route::delete('/shop/categories/{category}', [CategoryController::class, 'destroy'])
            ->middleware('catmin.permission:module.shop.delete')
            ->name('shop.categories.destroy');

        Route::get('/shop/customers', [CustomerController::class, 'index'])
            ->middleware('catmin.permission:module.shop.list')
            ->name('shop.customers.index');

        Route::get('/shop/customers/{customer}', [CustomerController::class, 'show'])
            ->middleware('catmin.permission:module.shop.list')
            ->name('shop.customers.show');

        Route::get('/shop/orders', [OrderController::class, 'index'])
            ->middleware('catmin.permission:module.shop.list')
            ->name('shop.orders.index');

        Route::get('/shop/orders/create', [OrderController::class, 'create'])
            ->middleware('catmin.permission:module.shop.create')
            ->name('shop.orders.create');

        Route::post('/shop/orders', [OrderController::class, 'store'])
            ->middleware('catmin.permission:module.shop.create')
            ->name('shop.orders.store');

        Route::get('/shop/orders/{order}', [OrderController::class, 'show'])
            ->middleware('catmin.permission:module.shop.list')
            ->name('shop.orders.show');

        Route::patch('/shop/orders/{order}/transition', [OrderController::class, 'transition'])
            ->middleware('catmin.permission:module.shop.config')
            ->name('shop.orders.transition');

        Route::get('/shop/invoices/{invoice}', [InvoiceController::class, 'show'])
            ->middleware('catmin.permission:module.shop.list')
            ->name('shop.invoices.show');

        Route::get('/shop/invoices/{invoice}/pdf', [InvoiceController::class, 'downloadPdf'])
            ->middleware('catmin.permission:module.shop.list')
            ->name('shop.invoices.pdf');

        Route::get('/shop/invoice-settings', [InvoiceController::class, 'settingsIndex'])
            ->middleware('catmin.permission:module.shop.config')
            ->name('shop.invoices.settings');

        Route::put('/shop/invoice-settings', [InvoiceController::class, 'settingsUpdate'])
            ->middleware('catmin.permission:module.shop.config')
            ->name('shop.invoices.settings.update');
    });
