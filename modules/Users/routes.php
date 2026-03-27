<?php

use Modules\Users\Controllers\Admin\RoleController;
use Modules\Users\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'catmin.admin'])
    ->prefix(config('catmin.admin.path', 'admin'))
    ->name('admin.')
    ->group(function (): void {
        Route::get('/users/manage', [UserController::class, 'index'])
            ->middleware('catmin.permission:module.users.list')
            ->name('users.manage');

        Route::get('/users/create', [UserController::class, 'create'])
            ->middleware('catmin.permission:module.users.create')
            ->name('users.create');

        Route::post('/users', [UserController::class, 'store'])
            ->middleware('catmin.permission:module.users.create')
            ->name('users.store');

        Route::get('/users/{user}/edit', [UserController::class, 'edit'])
            ->middleware('catmin.permission:module.users.edit')
            ->name('users.edit');

        Route::put('/users/{user}', [UserController::class, 'update'])
            ->middleware('catmin.permission:module.users.edit')
            ->name('users.update');

        Route::patch('/users/{user}/toggle-active', [UserController::class, 'toggleActive'])
            ->middleware('catmin.permission:module.users.config')
            ->name('users.toggle_active');

        Route::get('/roles/manage', [RoleController::class, 'index'])
            ->middleware('catmin.permission:module.users.config')
            ->name('roles.manage');
    });
