<?php

use Illuminate\Support\Facades\Route;
use Modules\Settings\Controllers\Admin\SettingsController;

Route::middleware(['web', 'catmin.admin'])
    ->prefix(config('catmin.admin.path', 'admin'))
    ->name('admin.')
    ->group(function (): void {
        Route::get('/settings/manage', [SettingsController::class, 'index'])
            ->name('settings.manage');

        Route::put('/settings/manage', [SettingsController::class, 'update'])
            ->name('settings.update');
    });
