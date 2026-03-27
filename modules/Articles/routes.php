<?php

use Illuminate\Support\Facades\Route;
use Modules\Articles\Controllers\Admin\ArticleController;

Route::middleware(['web', 'catmin.admin'])
    ->prefix(config('catmin.admin.path', 'admin'))
    ->name('admin.')
    ->group(function (): void {
        Route::get('/articles/manage', [ArticleController::class, 'index'])
            ->middleware('catmin.permission:module.articles.list')
            ->name('articles.manage');

        Route::get('/articles/create', [ArticleController::class, 'create'])
            ->middleware('catmin.permission:module.articles.create')
            ->name('articles.create');

        Route::post('/articles', [ArticleController::class, 'store'])
            ->middleware('catmin.permission:module.articles.create')
            ->name('articles.store');

        Route::get('/articles/{article}/edit', [ArticleController::class, 'edit'])
            ->middleware('catmin.permission:module.articles.edit')
            ->name('articles.edit');

        Route::put('/articles/{article}', [ArticleController::class, 'update'])
            ->middleware('catmin.permission:module.articles.edit')
            ->name('articles.update');

        Route::patch('/articles/{article}/toggle-status', [ArticleController::class, 'toggleStatus'])
            ->middleware('catmin.permission:module.articles.edit')
            ->name('articles.toggle_status');
    });
