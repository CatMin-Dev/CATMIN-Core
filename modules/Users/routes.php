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

        Route::delete('/users/{user}', [UserController::class, 'destroy'])
            ->middleware('catmin.permission:module.users.delete')
            ->name('users.destroy');

        Route::get('/roles/manage', [RoleController::class, 'index'])
            ->middleware('catmin.permission:module.users.config')
            ->name('roles.manage');

        Route::get('/roles/create', [RoleController::class, 'create'])
            ->middleware('catmin.permission:module.users.config')
            ->name('roles.create');

        Route::post('/roles', [RoleController::class, 'store'])
            ->middleware('catmin.permission:module.users.config')
            ->name('roles.store');

        Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])
            ->middleware('catmin.permission:module.users.config')
            ->name('roles.edit');

        Route::put('/roles/{role}', [RoleController::class, 'update'])
            ->middleware('catmin.permission:module.users.config')
            ->name('roles.update');

        Route::post('/roles/{role}/preview', [RoleController::class, 'startPreview'])
            ->middleware('catmin.permission:module.users.config')
            ->name('roles.preview.start');

        Route::delete('/roles/preview', [RoleController::class, 'stopPreview'])
            ->name('roles.preview.stop');

        Route::delete('/roles/{role}', [RoleController::class, 'destroy'])
            ->middleware('catmin.permission:module.users.config')
            ->name('roles.destroy');
    });
