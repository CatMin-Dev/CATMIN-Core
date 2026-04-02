<?php

use Illuminate\Support\Facades\Route;
use Modules\Webhooks\Controllers\Admin\WebhookController;
use Modules\Webhooks\Controllers\WebhookIncomingController;

// Public incoming endpoint — no admin middleware, rate-limited to 60/min per IP
Route::middleware(['web', 'throttle:60,1'])
    ->post('/webhooks/incoming/{token}', [WebhookIncomingController::class, 'receive'])
    ->name('webhooks.incoming');

// Admin CRUD
Route::middleware(['web', 'catmin.admin'])
    ->prefix(config('catmin.admin.path', 'admin'))
    ->name('admin.')
    ->group(function (): void {
        Route::get('/webhooks', [WebhookController::class, 'index'])
            ->middleware('catmin.permission:module.webhooks.list')
            ->name('webhooks.index');
        Route::get('/webhooks/create', [WebhookController::class, 'create'])
            ->middleware('catmin.permission:module.webhooks.create')
            ->name('webhooks.create');
        Route::post('/webhooks', [WebhookController::class, 'store'])
            ->middleware('catmin.permission:module.webhooks.create')
            ->name('webhooks.store');
        Route::get('/webhooks/{webhook}/edit', [WebhookController::class, 'edit'])
            ->middleware('catmin.permission:module.webhooks.edit')
            ->name('webhooks.edit');
        Route::put('/webhooks/{webhook}', [WebhookController::class, 'update'])
            ->middleware('catmin.permission:module.webhooks.edit')
            ->name('webhooks.update');
        Route::delete('/webhooks/{webhook}', [WebhookController::class, 'destroy'])
            ->middleware('catmin.permission:module.webhooks.delete')
            ->name('webhooks.destroy');

        Route::post('/webhooks/{webhook}/rotate-secret', [WebhookController::class, 'rotateSecret'])
            ->middleware('catmin.permission:module.webhooks.edit')
            ->name('webhooks.rotate-secret');

        Route::post('/webhooks/{webhook}/complete-rotation', [WebhookController::class, 'completeRotation'])
            ->middleware('catmin.permission:module.webhooks.edit')
            ->name('webhooks.complete-rotation');

        Route::post('/webhooks/bulk', [WebhookController::class, 'bulkAction'])
            ->middleware('catmin.permission:module.webhooks.list')
            ->name('webhooks.bulk');
    });
