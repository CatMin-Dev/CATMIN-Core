<?php

use Illuminate\Support\Facades\Route;
use Modules\Docs\Controllers\Admin\DocsController;

Route::middleware(['web', 'catmin.admin'])
    ->prefix(config('catmin.admin.path', 'admin'))
    ->name('admin.')
    ->group(function (): void {
        Route::get('/docs', [DocsController::class, 'index'])
            ->middleware('catmin.permission:module.docs.list')
            ->name('docs.index');

        Route::get('/docs/{slug}', [DocsController::class, 'show'])
            ->where('slug', '[a-zA-Z0-9\-_]+')
            ->middleware('catmin.permission:module.docs.list')
            ->name('docs.show');
    });
