<?php

use App\Services\CatminEventBus;

// Example addon hook registrations.
CatminEventBus::listen(CatminEventBus::CONTENT_CREATED, function (array $payload): void {
    \Log::info('example-addon hook: content created', $payload);
});

CatminEventBus::listen(CatminEventBus::SETTING_UPDATED, function (array $payload): void {
    \Log::info('example-addon hook: setting updated', $payload);
});
