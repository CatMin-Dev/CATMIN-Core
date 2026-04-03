<?php

use Addons\CatminProfileExtensions\Controllers\Admin\ProfileExtensionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'catmin.admin', 'catmin.locale'])
    ->prefix(config('catmin.admin.path', 'admin'))
    ->name('admin.profile.extensions.')
    ->group(function (): void {
        Route::put('/profile/extensions', [ProfileExtensionController::class, 'update'])
            ->middleware('catmin.permission:module.core.config')
            ->name('update');
    });
