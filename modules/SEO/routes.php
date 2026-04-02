<?php

use Illuminate\Support\Facades\Route;
use Modules\SEO\Controllers\Admin\SeoController;
use Modules\SEO\Controllers\Public\RobotsController;
use Modules\SEO\Controllers\Public\SitemapController;

Route::middleware(['web'])->group(function (): void {
    Route::get('/sitemap.xml', SitemapController::class)
        ->name('seo.sitemap');

    Route::get('/robots.txt', RobotsController::class)
        ->name('seo.robots');
});

Route::middleware(['web', 'catmin.admin'])
    ->prefix(config('catmin.admin.path', 'admin'))
    ->name('admin.')
    ->group(function (): void {
        Route::get('/seo/manage', [SeoController::class, 'index'])
            ->middleware('catmin.permission:module.seo.list')
            ->name('seo.manage');

        Route::get('/seo/create', [SeoController::class, 'create'])
            ->middleware('catmin.permission:module.seo.create')
            ->name('seo.create');

        Route::post('/seo', [SeoController::class, 'store'])
            ->middleware('catmin.permission:module.seo.create')
            ->name('seo.store');

        Route::get('/seo/{seoMeta}/edit', [SeoController::class, 'edit'])
            ->middleware('catmin.permission:module.seo.edit')
            ->name('seo.edit');

        Route::put('/seo/{seoMeta}', [SeoController::class, 'update'])
            ->middleware('catmin.permission:module.seo.edit')
            ->name('seo.update');

        Route::post('/seo/sitemap/refresh', [SeoController::class, 'refreshSitemap'])
            ->middleware('catmin.permission:module.seo.edit')
            ->name('seo.sitemap.refresh');
    });
