<?php

use App\Http\Controllers\Api\V2\PublicContentController;
use App\Http\Controllers\Api\V2\SystemController as ExternalSystemController;
use App\Http\Controllers\Api\V1\ArticlesController as V1ArticlesController;
use App\Http\Controllers\Api\V1\MediaAssetsController as V1MediaAssetsController;
use App\Http\Controllers\Api\V1\PagesController as V1PagesController;
use App\Http\Controllers\Api\V1\ShopProductsController as V1ShopProductsController;
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

Route::prefix('v1')
    ->middleware(['throttle:catmin-external-api', 'catmin.external-api-log'])
    ->group(function (): void {
        Route::prefix('pages')->group(function (): void {
            Route::get('/', [V1PagesController::class, 'index'])->middleware('catmin.api-v1-auth:pages.read');
            Route::get('/{id}', [V1PagesController::class, 'show'])->whereNumber('id')->middleware('catmin.api-v1-auth:pages.read');
            Route::post('/', [V1PagesController::class, 'store'])->middleware('catmin.api-v1-auth:pages.write');
            Route::put('/{id}', [V1PagesController::class, 'update'])->whereNumber('id')->middleware('catmin.api-v1-auth:pages.write');
            Route::patch('/{id}', [V1PagesController::class, 'update'])->whereNumber('id')->middleware('catmin.api-v1-auth:pages.write');
            Route::delete('/{id}', [V1PagesController::class, 'destroy'])->whereNumber('id')->middleware('catmin.api-v1-auth:pages.write');
        });

        Route::prefix('articles')->group(function (): void {
            Route::get('/', [V1ArticlesController::class, 'index'])->middleware('catmin.api-v1-auth:articles.read');
            Route::get('/{id}', [V1ArticlesController::class, 'show'])->whereNumber('id')->middleware('catmin.api-v1-auth:articles.read');
            Route::post('/', [V1ArticlesController::class, 'store'])->middleware('catmin.api-v1-auth:articles.write');
            Route::put('/{id}', [V1ArticlesController::class, 'update'])->whereNumber('id')->middleware('catmin.api-v1-auth:articles.write');
            Route::patch('/{id}', [V1ArticlesController::class, 'update'])->whereNumber('id')->middleware('catmin.api-v1-auth:articles.write');
            Route::delete('/{id}', [V1ArticlesController::class, 'destroy'])->whereNumber('id')->middleware('catmin.api-v1-auth:articles.write');
        });

        Route::prefix('media')->group(function (): void {
            Route::get('/', [V1MediaAssetsController::class, 'index'])->middleware('catmin.api-v1-auth:media.read');
            Route::get('/{id}', [V1MediaAssetsController::class, 'show'])->whereNumber('id')->middleware('catmin.api-v1-auth:media.read');
            Route::post('/', [V1MediaAssetsController::class, 'store'])->middleware('catmin.api-v1-auth:media.write');
            Route::put('/{id}', [V1MediaAssetsController::class, 'update'])->whereNumber('id')->middleware('catmin.api-v1-auth:media.write');
            Route::patch('/{id}', [V1MediaAssetsController::class, 'update'])->whereNumber('id')->middleware('catmin.api-v1-auth:media.write');
            Route::delete('/{id}', [V1MediaAssetsController::class, 'destroy'])->whereNumber('id')->middleware('catmin.api-v1-auth:media.write');
        });

        Route::prefix('shop/products')->group(function (): void {
            Route::get('/', [V1ShopProductsController::class, 'index'])->middleware('catmin.api-v1-auth:shop.read');
            Route::get('/{id}', [V1ShopProductsController::class, 'show'])->whereNumber('id')->middleware('catmin.api-v1-auth:shop.read');
            Route::post('/', [V1ShopProductsController::class, 'store'])->middleware('catmin.api-v1-auth:shop.write');
            Route::put('/{id}', [V1ShopProductsController::class, 'update'])->whereNumber('id')->middleware('catmin.api-v1-auth:shop.write');
            Route::patch('/{id}', [V1ShopProductsController::class, 'update'])->whereNumber('id')->middleware('catmin.api-v1-auth:shop.write');
            Route::delete('/{id}', [V1ShopProductsController::class, 'destroy'])->whereNumber('id')->middleware('catmin.api-v1-auth:shop.write');
        });
    });
