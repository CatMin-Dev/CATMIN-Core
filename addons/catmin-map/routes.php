<?php

use Addons\CatminMap\Controllers\Admin\GeoCategoryController;
use Addons\CatminMap\Controllers\Admin\GeoLocationController;
use Addons\CatminMap\Controllers\Admin\GeoMapController;
use Addons\CatminMap\Controllers\Api\GeoApiController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'catmin.admin'])
    ->prefix(config('catmin.admin.path', 'admin'))
    ->name('admin.map.')
    ->group(function (): void {

        // ── Interactive map view ──────────────────────────────────
        Route::get('/map', [GeoMapController::class, 'index'])
            ->middleware('catmin.permission:module.map.list')
            ->name('index');

        // ── Locations ─────────────────────────────────────────────
        Route::get('/map/locations', [GeoLocationController::class, 'index'])
            ->middleware('catmin.permission:module.map.list')
            ->name('locations.index');

        Route::get('/map/locations/create', [GeoLocationController::class, 'create'])
            ->middleware('catmin.permission:module.map.create')
            ->name('locations.create');

        Route::post('/map/locations', [GeoLocationController::class, 'store'])
            ->middleware('catmin.permission:module.map.create')
            ->name('locations.store');

        Route::get('/map/locations/{geoLocation}/edit', [GeoLocationController::class, 'edit'])
            ->middleware('catmin.permission:module.map.edit')
            ->name('locations.edit');

        Route::put('/map/locations/{geoLocation}', [GeoLocationController::class, 'update'])
            ->middleware('catmin.permission:module.map.edit')
            ->name('locations.update');

        Route::delete('/map/locations/{geoLocation}', [GeoLocationController::class, 'destroy'])
            ->middleware('catmin.permission:module.map.delete')
            ->name('locations.destroy');

        // ── Categories ────────────────────────────────────────────
        Route::get('/map/categories', [GeoCategoryController::class, 'index'])
            ->middleware('catmin.permission:module.map.list')
            ->name('categories.index');

        Route::post('/map/categories', [GeoCategoryController::class, 'store'])
            ->middleware('catmin.permission:module.map.create')
            ->name('categories.store');

        Route::put('/map/categories/{geoCategory}', [GeoCategoryController::class, 'update'])
            ->middleware('catmin.permission:module.map.edit')
            ->name('categories.update');

        Route::delete('/map/categories/{geoCategory}', [GeoCategoryController::class, 'destroy'])
            ->middleware('catmin.permission:module.map.delete')
            ->name('categories.destroy');

        // ── JSON API ──────────────────────────────────────────────
        Route::get('/map/api/points', [GeoApiController::class, 'points'])
            ->middleware('catmin.permission:module.map.api')
            ->name('api.points');
    });
