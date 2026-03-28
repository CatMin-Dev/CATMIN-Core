<?php

namespace App\Services;

class CatminEventMapService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function documentedEvents(): array
    {
        $domains = [
            'system' => [
                'module.discovered',
                'module.enabled',
                'module.disabled',
                'module.installed',
                'module.uninstalled',
                'addon.discovered',
                'addon.enabled',
                'addon.disabled',
                'addon.installed',
                'addon.uninstalled',
                'addon.booting',
                'addon.booted',
                'setting.created',
                'setting.updated',
                'setting.deleted',
                'config.cache.cleared',
                'system.health.checked',
                'system.maintenance.enabled',
                'system.maintenance.disabled',
                'system.update.started',
                'system.update.finished',
                'system.update.failed',
            ],
            'auth' => [
                'auth.login.succeeded',
                'auth.login.failed',
                'auth.logout',
                'auth.password.changed',
                'auth.password.reset.requested',
                'auth.password.reset.completed',
                'auth.2fa.enabled',
                'auth.2fa.disabled',
                'auth.2fa.challenge.passed',
                'auth.2fa.challenge.failed',
                'auth.2fa.recovery_code.used',
                'security.csrf.failed',
                'security.rate_limit.hit',
                'security.permission.denied',
                'security.suspicious.activity.detected',
            ],
            'iam' => [
                'user.created',
                'user.updated',
                'user.deleted',
                'user.activated',
                'user.deactivated',
                'role.created',
                'role.updated',
                'role.deleted',
                'role.protected.deletion_blocked',
                'permission.assigned',
                'permission.revoked',
                'role.assigned.to_user',
                'role.removed.from_user',
            ],
            'content' => [
                'page.created',
                'page.updated',
                'page.deleted',
                'page.published',
                'page.unpublished',
                'news.created',
                'news.updated',
                'news.deleted',
                'news.published',
                'blog.post.created',
                'blog.post.updated',
                'blog.post.deleted',
                'blog.post.published',
                'media.uploaded',
                'media.deleted',
                'media.replaced',
                'media.downloaded',
            ],
            'shop' => [
                'shop.product.created',
                'shop.product.updated',
                'shop.product.deleted',
                'shop.product.stock.low',
                'shop.product.stock.out',
                'shop.category.created',
                'shop.category.updated',
                'shop.category.deleted',
                'shop.order.created',
                'shop.order.updated',
                'shop.order.cancelled',
                'shop.order.paid',
                'shop.order.refunded',
                'shop.order.shipped',
                'shop.customer.created',
                'shop.customer.updated',
                'shop.invoice.generated',
                'shop.invoice.sent',
                'shop.invoice.failed',
            ],
            'mail' => [
                'mail.template.created',
                'mail.template.updated',
                'mail.template.deleted',
                'mail.sent',
                'mail.failed',
                'mail.queued',
                'mail.retrying',
                'notification.dispatched',
                'notification.failed',
            ],
            'api' => [
                'api.token.created',
                'api.token.revoked',
                'api.request.received',
                'api.request.failed',
                'api.rate_limit.hit',
                'webhook.received',
                'webhook.validated',
                'webhook.rejected',
                'webhook.dispatched',
                'webhook.delivery.failed',
                'webhook.delivery.succeeded',
            ],
            'observability' => [
                'audit.entry.created',
                'audit.export.generated',
                'log.error.recorded',
                'log.warning.recorded',
                'monitoring.health.failed',
                'monitoring.health.recovered',
                'monitoring.performance.threshold_exceeded',
            ],
            'admin_ui' => [
                'admin.sidebar.building',
                'admin.dashboard.widgets.collecting',
                'admin.header.actions.collecting',
                'admin.user.menu.collecting',
                'admin.help.links.collecting',
            ],
        ];

        $priorities = self::priorities();
        $rows = [];

        foreach ($domains as $domain => $events) {
            foreach ($events as $event) {
                $rows[] = [
                    'name' => $event,
                    'domain' => $domain,
                    'priority' => $priorities[$event] ?? 99,
                ];
            }
        }

        return $rows;
    }

    /**
     * @return array<int, string>
     */
    public static function implementedEvents(): array
    {
        return collect(CatminEventBus::events())
            ->map(fn (string $event) => self::trimCatminPrefix($event))
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public static function wiredEvents(): array
    {
        return [
            'addon.booting',
            'addon.booted',
            'addon.installed',
            'addon.enabled',
            'addon.disabled',
            'addon.uninstalled',
            'module.enabled',
            'module.disabled',
            'setting.updated',
            'user.created',
            'user.updated',
            'user.deleted',
            'page.published',
            'page.updated',
            'blog.post.published',
            'blog.post.updated',
            'auth.login.succeeded',
            'auth.login.failed',
            'auth.logout',
            'auth.password.reset.requested',
            'auth.password.reset.completed',
            'auth.2fa.challenge.passed',
            'auth.2fa.challenge.failed',
            'security.rate_limit.hit',
            'system.health.checked',
        ];
    }

    /**
     * @return array<string, int>
     */
    public static function priorities(): array
    {
        return [
            'auth.login.succeeded' => 1,
            'auth.login.failed' => 1,
            'auth.logout' => 1,
            'auth.2fa.challenge.passed' => 1,
            'auth.2fa.challenge.failed' => 1,
            'user.created' => 2,
            'user.updated' => 2,
            'user.deleted' => 2,
            'role.created' => 2,
            'role.updated' => 2,
            'role.deleted' => 2,
            'permission.assigned' => 2,
            'permission.revoked' => 2,
            'setting.updated' => 3,
            'setting.created' => 3,
            'setting.deleted' => 3,
            'module.enabled' => 4,
            'module.disabled' => 4,
            'addon.enabled' => 4,
            'addon.disabled' => 4,
            'api.request.received' => 5,
            'api.request.failed' => 5,
            'webhook.received' => 5,
            'webhook.delivery.failed' => 5,
            'shop.order.created' => 6,
            'shop.order.paid' => 6,
            'shop.invoice.generated' => 6,
            'mail.sent' => 7,
            'mail.failed' => 7,
            'mail.queued' => 7,
            'admin.sidebar.building' => 8,
            'admin.dashboard.widgets.collecting' => 8,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function status(): array
    {
        $documented = collect(self::documentedEvents());
        $documentedNames = $documented->pluck('name')->values();
        $implemented = collect(self::implementedEvents())->values();
        $wired = collect(self::wiredEvents())->values();

        $implementedDocumented = $implemented->intersect($documentedNames)->values();
        $documentedOnly = $documentedNames->diff($implemented)->values();
        $implementedNotDocumented = $implemented->diff($documentedNames)->values();

        $baseListeners = collect(CatminEventBus::registry())
            ->filter(fn (array $row) => (int) $row['listeners'] > 0)
            ->map(fn (array $row) => [
                'name' => self::trimCatminPrefix((string) $row['name']),
                'listeners' => (int) $row['listeners'],
            ])
            ->values();

        return [
            'counts' => [
                'documented' => $documentedNames->count(),
                'implemented' => $implemented->count(),
                'implemented_documented' => $implementedDocumented->count(),
                'wired' => $wired->count(),
                'documented_only' => $documentedOnly->count(),
                'implemented_not_documented' => $implementedNotDocumented->count(),
            ],
            'implemented' => $implementedDocumented->all(),
            'wired' => $wired->all(),
            'documented_only' => $documentedOnly->all(),
            'implemented_not_documented' => $implementedNotDocumented->all(),
            'base_listeners' => $baseListeners->all(),
            'priorities' => self::priorities(),
        ];
    }

    private static function trimCatminPrefix(string $event): string
    {
        return str_starts_with($event, 'catmin.')
            ? substr($event, strlen('catmin.'))
            : $event;
    }
}
