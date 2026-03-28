<?php

use App\Services\CatminEventBus;

// Example addon hook registrations.
CatminEventBus::subscribe([
    CatminEventBus::CONTENT_CREATED => function (array $payload): void {
        \Log::info('example-addon hook: content created', $payload);
    },
    CatminEventBus::SETTING_UPDATED => function (array $payload): void {
        \Log::info('example-addon hook: setting updated', $payload);
    },
    CatminEventBus::ADDON_ENABLED => function (array $payload): void {
        \Log::info('example-addon hook: addon enabled', $payload);
    },
    CatminEventBus::ADDON_UNINSTALLED => function (array $payload): void {
        \Log::info('example-addon hook: addon uninstalled', $payload);
    },
]);
