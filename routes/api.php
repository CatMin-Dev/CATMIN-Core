<?php

use App\Http\Controllers\Api\Internal\InternalPagesController;
use App\Http\Controllers\Api\Internal\InternalArticlesController;
use App\Http\Controllers\Api\Internal\InternalSettingsController;
use App\Http\Controllers\Api\Internal\InternalSystemController;
use Illuminate\Support\Facades\Route;

Route::prefix('internal')->group(function (): void {
    // Public useful data for frontend integrations.
    Route::get('/settings/public', [InternalSettingsController::class, 'publicSettings']);
    Route::get('/pages/published', [InternalPagesController::class, 'publishedPages']);
    Route::get('/articles/published', [InternalArticlesController::class, 'publishedArticles']);

    // Protected internal diagnostics.
    Route::middleware('catmin.api-token')->group(function (): void {
        Route::get('/system/status', [InternalSystemController::class, 'status']);
        Route::get('/system/version', [InternalSystemController::class, 'version']);
        Route::get('/system/health', [InternalSystemController::class, 'health']);
    });
});
