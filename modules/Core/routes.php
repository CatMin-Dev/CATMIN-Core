<?php

use Illuminate\Support\Facades\Route;
use Modules\Core\Services\CoreFoundationService;

Route::middleware('web')
    ->prefix(config('catmin.admin.path', 'admin'))
    ->name('admin.core.')
    ->group(function (): void {
        Route::get('/core/status', function () {
            abort_unless(session('catmin_admin_authenticated', false), 403);

            return response()->json(CoreFoundationService::status());
        })->name('status');
    });
