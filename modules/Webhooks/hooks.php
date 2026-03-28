<?php

use App\Services\CatminEventBus;
use App\Services\CatminHookRegistry;
use Modules\Webhooks\Services\WebhookDispatcher;

CatminHookRegistry::after('admin.footer', '<!-- catmin:webhooks hooks ready -->');

CatminEventBus::listen(CatminEventBus::USER_CREATED, function (array $payload): void {
    WebhookDispatcher::dispatch('user.created', $payload);
});

CatminEventBus::listen(CatminEventBus::USER_UPDATED, function (array $payload): void {
    WebhookDispatcher::dispatch('user.updated', $payload);
});

CatminEventBus::listen(CatminEventBus::USER_DELETED, function (array $payload): void {
    WebhookDispatcher::dispatch('user.deleted', $payload);
});

CatminEventBus::listen(CatminEventBus::PAGE_PUBLISHED, function (array $payload): void {
    WebhookDispatcher::dispatch('page.published', $payload);
});

CatminEventBus::listen(CatminEventBus::PAGE_UPDATED, function (array $payload): void {
    WebhookDispatcher::dispatch('page.updated', $payload);
});

CatminEventBus::listen(CatminEventBus::ARTICLE_PUBLISHED, function (array $payload): void {
    WebhookDispatcher::dispatch('article.published', $payload);
});

CatminEventBus::listen(CatminEventBus::ARTICLE_UPDATED, function (array $payload): void {
    WebhookDispatcher::dispatch('article.updated', $payload);
});

CatminEventBus::listen(CatminEventBus::SETTING_UPDATED, function (array $payload): void {
    WebhookDispatcher::dispatch('settings.updated', $payload);
});
