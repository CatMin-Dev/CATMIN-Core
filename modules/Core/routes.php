<?php

use Illuminate\Support\Facades\Route;
use Modules\Core\Services\CoreFoundationService;

Route::middleware(['web', 'catmin.admin'])
    ->prefix(config('catmin.admin.path', 'admin'))
    ->name('admin.core.')
    ->group(function (): void {
        Route::get('/core/status', function () {
            return response()->json(CoreFoundationService::status());
        })->middleware('catmin.permission:module.core.list')->name('status');
    });
