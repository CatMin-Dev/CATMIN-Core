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

        // Legacy combined update
        Route::put('/settings/manage', [SettingsController::class, 'update'])
            ->middleware('catmin.permission:module.settings.config')
            ->name('settings.update');

        // Panel-specific updates
        Route::put('/settings/site', [SettingsController::class, 'updateSite'])
            ->middleware('catmin.permission:module.settings.config')
            ->name('settings.update.site');

        Route::put('/settings/admin', [SettingsController::class, 'updateAdmin'])
            ->middleware('catmin.permission:module.settings.config')
            ->name('settings.update.admin');

        Route::put('/settings/security', [SettingsController::class, 'updateSecurity'])
            ->middleware('catmin.permission:module.settings.config')
            ->name('settings.update.security');

        Route::put('/settings/mailer', [SettingsController::class, 'updateMailer'])
            ->middleware('catmin.permission:module.settings.config')
            ->name('settings.update.mailer');

        Route::put('/settings/shop', [SettingsController::class, 'updateShop'])
            ->middleware('catmin.permission:module.settings.config')
            ->name('settings.update.shop');

        Route::put('/settings/ops', [SettingsController::class, 'updateOps'])
            ->middleware('catmin.permission:module.settings.config')
            ->name('settings.update.ops');

        Route::put('/settings/docs', [SettingsController::class, 'updateDocs'])
            ->middleware('catmin.permission:module.settings.config')
            ->name('settings.update.docs');

        Route::put('/settings/seo', [SettingsController::class, 'updateSeo'])
            ->middleware('catmin.permission:module.settings.config')
            ->name('settings.update.seo');
    });

