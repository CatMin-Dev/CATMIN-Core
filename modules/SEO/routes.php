<?php

use Illuminate\Support\Facades\Route;
use Modules\SEO\Controllers\Admin\SeoController;

Route::middleware(['web', 'catmin.admin'])
    ->prefix(config('catmin.admin.path', 'admin'))
    ->name('admin.')
    ->group(function (): void {
        Route::get('/seo/manage', [SeoController::class, 'index'])
            ->name('seo.manage');

        Route::get('/seo/create', [SeoController::class, 'create'])
            ->name('seo.create');

        Route::post('/seo', [SeoController::class, 'store'])
            ->name('seo.store');

        Route::get('/seo/{seoMeta}/edit', [SeoController::class, 'edit'])
            ->name('seo.edit');

        Route::put('/seo/{seoMeta}', [SeoController::class, 'update'])
            ->name('seo.update');
    });
