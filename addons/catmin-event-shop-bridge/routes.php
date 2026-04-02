<?php

use Illuminate\Support\Facades\Route;
use Addons\CatminEventShopBridge\Controllers\Admin\TicketTypeController;

Route::middleware(['web', 'catmin.admin'])
    ->prefix(config('catmin.admin.path', 'admin'))
    ->name('admin.')
    ->group(function (): void {
        Route::get('/event-shop-bridge/ticket-types', [TicketTypeController::class, 'index'])
            ->middleware('catmin.permission:module.event_shop_bridge.list')
            ->name('event-shop-bridge.ticket-types.index');

        Route::post('/event-shop-bridge/ticket-types', [TicketTypeController::class, 'store'])
            ->middleware('catmin.permission:module.event_shop_bridge.create')
            ->name('event-shop-bridge.ticket-types.store');
    });