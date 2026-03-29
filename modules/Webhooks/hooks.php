<?php

use App\Services\CatminEventBus;
use App\Services\CatminHookRegistry;
use App\Services\Dashboard\DashboardWidgetRegistry;
use Modules\Webhooks\Services\WebhookDispatcher;

CatminHookRegistry::after('admin.footer', '<!-- catmin:webhooks hooks ready -->');

DashboardWidgetRegistry::register(function (array $context): array {
    $dashboard = (array) ($context['dashboard'] ?? []);
    $kpiIndex = (array) ($dashboard['kpi_index'] ?? []);

    return [
        'id' => 'module-webhooks-health',
        'title' => 'Module Webhooks - stabilite',
        'subtitle' => 'Widget injecte par modules/Webhooks/hooks.php',
        'tone' => ((int) ($kpiIndex['webhooks_failed_24h'] ?? 0)) > 0 ? 'warning' : 'info',
        'order' => 35,
        'items' => [
            [
                'primary' => 'Echecs 24h',
                'secondary' => (string) ((int) ($kpiIndex['webhooks_failed_24h'] ?? 0)) . ' delivery(s) KO/retrying',
                'meta' => ((int) ($kpiIndex['webhooks_failed_24h'] ?? 0)) > 0 ? 'Action conseillee: verifier endpoint / secret' : 'Flux stable',
            ],
            [
                'primary' => 'Etat module',
                'secondary' => 'Module actif et hooks charges',
                'meta' => 'Injection dashboard operationnelle',
            ],
        ],
        'empty' => 'Aucune donnee webhook.',
        'action' => [
            'label' => 'Ouvrir Webhooks',
            'url' => route('admin.webhooks.index'),
            'permission' => 'module.webhooks.list',
        ],
    ];
}, 35);

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
