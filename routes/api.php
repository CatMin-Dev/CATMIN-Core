<?php

use App\Http\Controllers\Api\V2\PublicContentController;
use App\Http\Controllers\Api\V2\SystemController as ExternalSystemController;
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

Route::prefix('v2')
    ->middleware(['throttle:catmin-external-api', 'catmin.external-api-log'])
    ->group(function (): void {
        // Public endpoints
        Route::get('/health', [ExternalSystemController::class, 'health']);
        Route::get('/version', [ExternalSystemController::class, 'version']);
        Route::get('/settings/public', [PublicContentController::class, 'settings']);
        Route::get('/pages/published', [PublicContentController::class, 'pages']);
        Route::get('/articles/published', [PublicContentController::class, 'articles']);
        Route::get('/shop/products', [PublicContentController::class, 'shopProducts']);

        // Protected external endpoints
        Route::middleware('catmin.external-api-key:external.read')->group(function (): void {
            Route::get('/system/status', [ExternalSystemController::class, 'status']);
        });
    });
