<?php

use Addons\CatminImportExport\Controllers\Admin\ImportExportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'catmin.admin'])
    ->prefix(config('catmin.admin.path', 'admin'))
    ->name('admin.import_export.')
    ->group(function (): void {
        Route::get('/import-export', [ImportExportController::class, 'index'])
            ->middleware('catmin.permission:module.import_export.menu')
            ->name('index');

        Route::post('/import-export/export', [ImportExportController::class, 'export'])
            ->middleware('catmin.permission:module.import_export.use')
            ->name('export');

        Route::post('/import-export/import', [ImportExportController::class, 'import'])
            ->middleware('catmin.permission:module.import_export.use')
            ->name('import');
    });