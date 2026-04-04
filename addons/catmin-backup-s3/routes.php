<?php

use Addons\CatminBackupS3\Controllers\Admin\RemoteBackupController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'catmin.admin', 'catmin.locale'])
    ->prefix(config('catmin.admin.path', 'admin'))
    ->name('admin.backup.remote.')
    ->group(function (): void {
        Route::get('/backup/remote', [RemoteBackupController::class, 'index'])
            ->middleware('catmin.permission:backup.remote.index')
            ->name('index');

        Route::put('/backup/remote/settings', [RemoteBackupController::class, 'updateSettings'])
            ->middleware('catmin.permission:backup.remote.manage')
            ->name('settings.update');

        Route::post('/backup/remote/test', [RemoteBackupController::class, 'testConnection'])
            ->middleware('catmin.permission:backup.remote.manage')
            ->name('test');

        Route::post('/backup/remote/upload', [RemoteBackupController::class, 'upload'])
            ->middleware('catmin.permission:backup.remote.upload')
            ->name('upload');

        Route::post('/backup/remote/retention', [RemoteBackupController::class, 'runRetention'])
            ->middleware('catmin.permission:backup.remote.manage')
            ->name('retention');

        Route::post('/backup/remote/download', [RemoteBackupController::class, 'download'])
            ->middleware('catmin.permission:backup.remote.download')
            ->name('download');
    });
