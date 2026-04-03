<?php

use Addons\CatminSlider\Controllers\Admin\SliderController;
use Addons\CatminSlider\Controllers\Admin\SliderItemController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'catmin.admin', 'catmin.locale'])
    ->prefix(config('catmin.admin.path', 'admin'))
    ->name('admin.slider.')
    ->group(function (): void {

        // ── Sliders CRUD ──────────────────────────────────────────────────────
        Route::get('/sliders', [SliderController::class, 'index'])
            ->middleware('catmin.permission:slider.index')
            ->name('index');

        Route::get('/sliders/create', [SliderController::class, 'create'])
            ->middleware('catmin.permission:slider.create')
            ->name('create');

        Route::post('/sliders', [SliderController::class, 'store'])
            ->middleware('catmin.permission:slider.create')
            ->name('store');

        Route::get('/sliders/{slider}', [SliderController::class, 'edit'])
            ->middleware('catmin.permission:slider.index')
            ->name('edit');

        Route::put('/sliders/{slider}', [SliderController::class, 'update'])
            ->middleware('catmin.permission:slider.update')
            ->name('update');

        Route::delete('/sliders/{slider}', [SliderController::class, 'destroy'])
            ->middleware('catmin.permission:slider.delete')
            ->name('destroy');

        Route::patch('/sliders/{slider}/toggle', [SliderController::class, 'toggle'])
            ->middleware('catmin.permission:slider.update')
            ->name('toggle');

        // ── Slider Items ──────────────────────────────────────────────────────
        Route::post('/sliders/{slider}/items', [SliderItemController::class, 'store'])
            ->middleware('catmin.permission:slider.update')
            ->name('items.store');

        Route::put('/sliders/{slider}/items/{sliderItem}', [SliderItemController::class, 'update'])
            ->middleware('catmin.permission:slider.update')
            ->name('items.update');

        Route::delete('/sliders/{slider}/items/{sliderItem}', [SliderItemController::class, 'destroy'])
            ->middleware('catmin.permission:slider.update')
            ->name('items.destroy');

        Route::post('/sliders/{slider}/items/reorder', [SliderItemController::class, 'reorder'])
            ->middleware('catmin.permission:slider.update')
            ->name('items.reorder');
    });
