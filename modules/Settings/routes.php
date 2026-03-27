<?php

use Illuminate\Support\Facades\Route;
use Modules\Settings\Controllers\Admin\SettingsController;

Route::middleware(['web', 'catmin.admin'])
    ->prefix(config('catmin.admin.path', 'admin'))
    ->name('admin.')
    ->group(function (): void {
        Route::get('/settings/manage', [SettingsController::class, 'index'])
            ->middleware('catmin.permission:module.settings.list')
            ->name('settings.manage');

        Route::put('/settings/manage', [SettingsController::class, 'update'])
            ->middleware('catmin.permission:module.settings.config')
            ->name('settings.update');
    });
