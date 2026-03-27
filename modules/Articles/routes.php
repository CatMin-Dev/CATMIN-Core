<?php

use Illuminate\Support\Facades\Route;
use Modules\Articles\Controllers\Admin\ArticleController;

Route::middleware(['web', 'catmin.admin'])
    ->prefix(config('catmin.admin.path', 'admin'))
    ->name('admin.')
    ->group(function (): void {
        Route::get('/articles/manage', [ArticleController::class, 'index'])
            ->name('articles.manage');

        Route::get('/articles/create', [ArticleController::class, 'create'])
            ->name('articles.create');

        Route::post('/articles', [ArticleController::class, 'store'])
            ->name('articles.store');

        Route::get('/articles/{article}/edit', [ArticleController::class, 'edit'])
            ->name('articles.edit');

        Route::put('/articles/{article}', [ArticleController::class, 'update'])
            ->name('articles.update');

        Route::patch('/articles/{article}/toggle-status', [ArticleController::class, 'toggleStatus'])
            ->name('articles.toggle_status');
    });
