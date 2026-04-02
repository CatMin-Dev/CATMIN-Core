<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

require_once __DIR__ . '/Controllers/Admin/CatWysiwygAdminController.php';

View::addNamespace('addon_cat_wysiwyg', __DIR__ . '/Views');

Route::middleware(['web', 'catmin.admin'])
    ->prefix(config('catmin.admin.path', 'admin'))
    ->name('admin.')
    ->group(function (): void {
        Route::get('/addons/cat-wysiwyg', [\Addons\CatWysiwyg\Controllers\Admin\CatWysiwygAdminController::class, 'index'])
            ->middleware('catmin.permission:addon.cat_wysiwyg.menu')
            ->name('addon.cat_wysiwyg.index');

        Route::put('/addons/cat-wysiwyg', [\Addons\CatWysiwyg\Controllers\Admin\CatWysiwygAdminController::class, 'update'])
            ->middleware('catmin.permission:addon.cat_wysiwyg.config')
            ->name('addon.cat_wysiwyg.update');

        Route::get('/addons/cat-wysiwyg/library', [\Addons\CatWysiwyg\Controllers\Admin\CatWysiwygAdminController::class, 'library'])
            ->middleware('catmin.permission:addon.cat_wysiwyg.config')
            ->name('addon.cat_wysiwyg.library');

        Route::put('/addons/cat-wysiwyg/library', [\Addons\CatWysiwyg\Controllers\Admin\CatWysiwygAdminController::class, 'updateLibrary'])
            ->middleware('catmin.permission:addon.cat_wysiwyg.config')
            ->name('addon.cat_wysiwyg.library.update');
    });
