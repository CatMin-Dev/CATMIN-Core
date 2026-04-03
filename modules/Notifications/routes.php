<?php

use Illuminate\Support\Facades\Route;
use Modules\Notifications\Controllers\Admin\NotificationController;

Route::middleware(['web', 'catmin.admin'])
    ->prefix(config('catmin.admin.path', 'admin'))
    ->name('admin.')
    ->group(function (): void {
        Route::get('/notifications', [NotificationController::class, 'index'])
            ->middleware('catmin.permission:module.notifications.list')
            ->name('notifications.index');

        Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])
            ->middleware('catmin.permission:module.notifications.read')
            ->name('notifications.read');

        Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])
            ->middleware('catmin.permission:module.notifications.read')
            ->name('notifications.read-all');

        Route::post('/notifications/{notification}/acknowledge', [NotificationController::class, 'acknowledge'])
            ->middleware('catmin.permission:module.notifications.acknowledge')
            ->name('notifications.acknowledge');

        Route::post('/notifications/bulk', [NotificationController::class, 'bulk'])
            ->middleware('catmin.permission:module.notifications.read')
            ->name('notifications.bulk');

        Route::post('/notifications/aggregate', [NotificationController::class, 'aggregate'])
            ->middleware('catmin.permission:module.notifications.manage')
            ->name('notifications.aggregate');

        Route::get('/notifications/dropdown', [NotificationController::class, 'dropdownData'])
            ->middleware('catmin.permission:module.notifications.list')
            ->name('notifications.dropdown');
    });
