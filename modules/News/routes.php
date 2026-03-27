<?php

use Illuminate\Support\Facades\Route;
use Modules\News\Controllers\Admin\NewsController;

Route::middleware(['web', 'catmin.admin'])
    ->prefix(config('catmin.admin.path', 'admin'))
    ->name('admin.')
    ->group(function (): void {
        Route::get('/news/manage', [NewsController::class, 'index'])
            ->name('news.manage');

        Route::get('/news/create', [NewsController::class, 'create'])
            ->name('news.create');

        Route::post('/news', [NewsController::class, 'store'])
            ->name('news.store');

        Route::get('/news/{newsItem}/edit', [NewsController::class, 'edit'])
            ->name('news.edit');

        Route::put('/news/{newsItem}', [NewsController::class, 'update'])
            ->name('news.update');

        Route::patch('/news/{newsItem}/toggle-status', [NewsController::class, 'toggleStatus'])
            ->name('news.toggle_status');
    });
