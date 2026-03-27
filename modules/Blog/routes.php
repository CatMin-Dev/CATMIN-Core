<?php

use Illuminate\Support\Facades\Route;
use Modules\Blog\Controllers\Admin\BlogController;

Route::middleware(['web', 'catmin.admin'])
    ->prefix(config('catmin.admin.path', 'admin'))
    ->name('admin.')
    ->group(function (): void {
        Route::get('/blog/manage', [BlogController::class, 'index'])
            ->name('blog.manage');

        Route::get('/blog/create', [BlogController::class, 'create'])
            ->name('blog.create');

        Route::post('/blog', [BlogController::class, 'store'])
            ->name('blog.store');

        Route::get('/blog/{blogPost}/edit', [BlogController::class, 'edit'])
            ->name('blog.edit');

        Route::put('/blog/{blogPost}', [BlogController::class, 'update'])
            ->name('blog.update');

        Route::patch('/blog/{blogPost}/toggle-status', [BlogController::class, 'toggleStatus'])
            ->name('blog.toggle_status');
    });
