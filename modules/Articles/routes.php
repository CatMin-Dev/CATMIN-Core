<?php

use Illuminate\Support\Facades\Route;
use Modules\Articles\Controllers\Admin\ArticleController;
use Modules\Articles\Controllers\Admin\ArticleCategoryController;
use Modules\Articles\Controllers\Admin\ArticleTagController;

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

        Route::delete('/articles/trash/empty', [ArticleController::class, 'emptyTrash'])
            ->middleware('catmin.permission:module.articles.trash')
            ->name('articles.trash.empty');

        Route::get('/articles/{article}/edit', [ArticleController::class, 'edit'])
            ->middleware('catmin.permission:module.articles.edit')
            ->name('articles.edit');

        Route::put('/articles/{article}', [ArticleController::class, 'update'])
            ->middleware('catmin.permission:module.articles.edit')
            ->name('articles.update');

        Route::match(['post', 'put'], '/articles/preview', [ArticleController::class, 'preview'])
            ->name('articles.preview');

        Route::patch('/articles/{article}/toggle-status', [ArticleController::class, 'toggleStatus'])
            ->middleware('catmin.permission:module.articles.edit')
            ->name('articles.toggle_status');

        Route::delete('/articles/{article}', [ArticleController::class, 'destroy'])
            ->middleware('catmin.permission:module.articles.trash')
            ->name('articles.destroy');

        Route::patch('/articles/{article}/restore', [ArticleController::class, 'restore'])
            ->middleware('catmin.permission:module.articles.trash')
            ->name('articles.restore');

        Route::delete('/articles/{article}/force-delete', [ArticleController::class, 'forceDelete'])
            ->middleware('catmin.permission:module.articles.trash')
            ->name('articles.force_delete');

        Route::post('/articles/bulk', [ArticleController::class, 'bulkAction'])
            ->middleware('catmin.permission:module.articles.list')
            ->name('articles.bulk');

        Route::get('/articles/categories', [ArticleCategoryController::class, 'index'])
            ->middleware('catmin.permission:module.articles.config')
            ->name('articles.categories.index');

        Route::post('/articles/categories', [ArticleCategoryController::class, 'store'])
            ->middleware('catmin.permission:module.articles.config')
            ->name('articles.categories.store');

        Route::get('/articles/categories/{category}/edit', [ArticleCategoryController::class, 'edit'])
            ->middleware('catmin.permission:module.articles.config')
            ->name('articles.categories.edit');

        Route::put('/articles/categories/{category}', [ArticleCategoryController::class, 'update'])
            ->middleware('catmin.permission:module.articles.config')
            ->name('articles.categories.update');

        Route::delete('/articles/categories/{category}', [ArticleCategoryController::class, 'destroy'])
            ->middleware('catmin.permission:module.articles.config')
            ->name('articles.categories.destroy');

        Route::get('/articles/tags', [ArticleTagController::class, 'index'])
            ->middleware('catmin.permission:module.articles.config')
            ->name('articles.tags.index');

        Route::post('/articles/tags', [ArticleTagController::class, 'store'])
            ->middleware('catmin.permission:module.articles.config')
            ->name('articles.tags.store');

        Route::get('/articles/tags/{tag}/edit', [ArticleTagController::class, 'edit'])
            ->middleware('catmin.permission:module.articles.config')
            ->name('articles.tags.edit');

        Route::put('/articles/tags/{tag}', [ArticleTagController::class, 'update'])
            ->middleware('catmin.permission:module.articles.config')
            ->name('articles.tags.update');

        Route::delete('/articles/tags/{tag}', [ArticleTagController::class, 'destroy'])
            ->middleware('catmin.permission:module.articles.config')
            ->name('articles.tags.destroy');
    });
