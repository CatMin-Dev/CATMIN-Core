<?php

use App\Services\CatminEventBus;
use Addons\CatminEventShopBridge\Services\EventShopBridgeService;

CatminEventBus::listen('shop.order.paid', function (array $payload): void {
    $orderId = (int) ($payload['order_id'] ?? 0);
    if ($orderId > 0) {
        app(EventShopBridgeService::class)->handlePaidOrder($orderId);
    }
});

CatminEventBus::listen('shop.order.cancelled', function (array $payload): void {
    $orderId = (int) ($payload['order_id'] ?? 0);
    if ($orderId > 0) {
        app(EventShopBridgeService::class)->handleCancelledOrder($orderId);
    }
});