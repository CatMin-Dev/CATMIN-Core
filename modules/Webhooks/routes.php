<?php

use Illuminate\Support\Facades\Route;
use Modules\Webhooks\Controllers\Admin\WebhookController;
use Modules\Webhooks\Controllers\WebhookIncomingController;

// Public incoming endpoint — no admin middleware
Route::middleware(['web'])
    ->post('/webhooks/incoming/{token}', [WebhookIncomingController::class, 'receive'])
    ->name('webhooks.incoming');

// Admin CRUD
Route::middleware(['web', 'catmin.admin'])
    ->prefix(config('catmin.admin.path', 'admin'))
    ->name('admin.')
    ->group(function (): void {
        Route::get('/webhooks', [WebhookController::class, 'index'])->name('webhooks.index');
        Route::get('/webhooks/create', [WebhookController::class, 'create'])->name('webhooks.create');
        Route::post('/webhooks', [WebhookController::class, 'store'])->name('webhooks.store');
        Route::get('/webhooks/{webhook}/edit', [WebhookController::class, 'edit'])->name('webhooks.edit');
        Route::put('/webhooks/{webhook}', [WebhookController::class, 'update'])->name('webhooks.update');
        Route::delete('/webhooks/{webhook}', [WebhookController::class, 'destroy'])->name('webhooks.destroy');
    });
