<?php

namespace App\Services\Dashboard;

use App\Services\ModuleManager;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class DashboardKpiService
{
    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        $kpiIndex = [
            'active_admin_sessions' => $this->activeAdminSessions(),
            'roles_total' => $this->countTable('roles'),
            'admin_accounts' => $this->adminAccountsCount(),
            'pages_published' => $this->countByStatus('pages', 'published'),
            'pages_draft' => $this->countByNotStatus('pages', 'published'),
            'articles_published' => $this->countByStatus('articles', 'published'),
            'articles_draft' => $this->countByNotStatus('articles', 'published'),
            'media_total' => $this->countTable('media_assets'),
            'shop_orders_pending' => $this->countPendingOrders(),
            'failed_jobs' => $this->countTable('failed_jobs'),
            'webhooks_failed_24h' => $this->countWebhookFailedLast24h(),
            'critical_errors_24h' => $this->countCriticalErrorsLast24h(),
            'emails_failed_24h' => $this->countFailedEmailsLast24h(),
            'low_stock_products' => $this->countLowStockProducts(),
        ];

        return [
            'kpi_index' => $kpiIndex,
            'kpis' => $this->kpiCards($kpiIndex),
            'alerts' => $this->alertRows($kpiIndex),
            'quick_actions' => $this->quickActions(),
            'module_health' => $this->moduleHealthRows(),
            'widgets' => $this->defaultWidgets($kpiIndex),
            'generated_at' => now(),
        ];
    }

    private function activeAdminSessions(): int
    {
        if (!$this->hasColumns('admin_sessions', ['session_id', 'last_activity_at', 'revoked_at'])) {
            return 0;
        }

        return (int) DB::table('admin_sessions')
            ->whereNull('revoked_at')
            ->where('last_activity_at', '>=', now()->subMinutes(15))
            ->count();
    }

    private function adminAccountsCount(): int
    {
        if (!$this->hasTable('admin_users')) {
            return 0;
        }

        if ($this->hasColumns('admin_users', ['is_active'])) {
            return (int) DB::table('admin_users')->where('is_active', true)->count();
        }

        return $this->countTable('admin_users');
    }

    private function countPendingOrders(): int
    {
        if (!$this->hasColumns('shop_orders', ['status'])) {
            return 0;
        }

        return (int) DB::table('shop_orders')
            ->whereIn('status', ['pending', 'payment_pending'])
            ->count();
    }

    private function countWebhookFailedLast24h(): int
    {
        if (!$this->hasColumns('webhook_deliveries', ['status', 'created_at'])) {
            return 0;
        }

        return (int) DB::table('webhook_deliveries')
            ->whereIn('status', ['failed', 'retrying'])
            ->where('created_at', '>=', now()->subDay())
            ->count();
    }

    private function countCriticalErrorsLast24h(): int
    {
        if (!$this->hasColumns('system_logs', ['level', 'created_at'])) {
            return 0;
        }

        return (int) DB::table('system_logs')
            ->whereIn('level', ['error', 'critical', 'alert', 'emergency'])
            ->where('created_at', '>=', now()->subDay())
            ->count();
    }

    private function countFailedEmailsLast24h(): int
    {
        if (!$this->hasColumns('mailer_history', ['status', 'created_at'])) {
            return 0;
        }

        return (int) DB::table('mailer_history')
            ->where('status', 'failed')
            ->where('created_at', '>=', now()->subDay())
            ->count();
    }

    private function countLowStockProducts(): int
    {
        if (!$this->hasColumns('shop_products', ['manage_stock', 'stock_quantity', 'low_stock_threshold'])) {
            return 0;
        }

        return (int) DB::table('shop_products')
            ->where('manage_stock', true)
            ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
            ->count();
    }

    /**
     * @param array<string, int> $index
     * @return array<int, array<string, mixed>>
     */
    private function kpiCards(array $index): array
    {
        return [
            [
                'id' => 'active_admin_sessions',
                'label' => 'Admins actifs (15m)',
                'value' => $index['active_admin_sessions'],
                'description' => 'Sessions admin en cours',
                'icon' => 'bi bi-person-badge',
                'permission' => 'module.core.list',
                'url' => $this->routeUrl('sessions.index'),
            ],
            [
                'id' => 'content_pages',
                'label' => 'Pages publiees / brouillons',
                'value' => $index['pages_published'] . ' / ' . $index['pages_draft'],
                'description' => 'Etat du contenu pages',
                'icon' => 'bi bi-file-earmark-richtext',
                'permission' => 'module.pages.list',
                'url' => $this->routeUrl('pages.manage'),
            ],
            [
                'id' => 'content_articles',
                'label' => 'Articles publies / brouillons',
                'value' => $index['articles_published'] . ' / ' . $index['articles_draft'],
                'description' => 'Etat editorial',
                'icon' => 'bi bi-journal-text',
                'permission' => 'module.articles.list',
                'url' => $this->routeUrl('articles.manage'),
            ],
            [
                'id' => 'media_total',
                'label' => 'Medias total',
                'value' => $index['media_total'],
                'description' => 'Bibliotheque assets',
                'icon' => 'bi bi-image',
                'permission' => 'module.media.list',
                'url' => $this->routeUrl('media.manage'),
            ],
            [
                'id' => 'shop_pending',
                'label' => 'Commandes en attente',
                'value' => $index['shop_orders_pending'],
                'description' => 'A traiter rapidement',
                'icon' => 'bi bi-bag-check',
                'permission' => 'module.shop.list',
                'url' => $this->routeUrl('shop.orders.index'),
            ],
            [
                'id' => 'failed_jobs',
                'label' => 'Jobs en echec',
                'value' => $index['failed_jobs'],
                'description' => 'Queue a relancer',
                'icon' => 'bi bi-exclamation-triangle',
                'permission' => 'module.queue.list',
                'url' => $this->routeUrl('queue.index'),
            ],
            [
                'id' => 'webhooks_failed',
                'label' => 'Webhooks KO (24h)',
                'value' => $index['webhooks_failed_24h'],
                'description' => 'Deliveries failed/retrying',
                'icon' => 'bi bi-broadcast-pin',
                'permission' => 'module.webhooks.list',
                'url' => $this->routeUrl('webhooks.index'),
            ],
            [
                'id' => 'critical_errors',
                'label' => 'Erreurs critiques (24h)',
                'value' => $index['critical_errors_24h'],
                'description' => 'Niveaux error+',
                'icon' => 'bi bi-bug',
                'permission' => 'module.logger.list',
                'url' => $this->routeUrl('logger.index'),
            ],
            [
                'id' => 'emails_failed',
                'label' => 'Emails echoues (24h)',
                'value' => $index['emails_failed_24h'],
                'description' => 'Mailer failures',
                'icon' => 'bi bi-envelope-x',
                'permission' => 'module.mailer.list',
                'url' => $this->routeUrl('mailer.manage'),
            ],
            [
                'id' => 'low_stock',
                'label' => 'Produits stock bas',
                'value' => $index['low_stock_products'],
                'description' => 'Seuil mini atteint',
                'icon' => 'bi bi-box-seam',
                'permission' => 'module.shop.list',
                'url' => $this->routeUrl('shop.manage'),
            ],
        ];
    }

    /**
     * @param array<string, int> $index
     * @return array<int, array<string, mixed>>
     */
    private function alertRows(array $index): array
    {
        $alerts = [];

        if (app()->isDownForMaintenance()) {
            $alerts[] = [
                'severity' => 'critical',
                'title' => 'Mode maintenance actif',
                'message' => 'Le front public est actuellement en maintenance.',
                'url' => null,
                'permission' => null,
            ];
        }

        if ($index['critical_errors_24h'] > 0) {
            $alerts[] = [
                'severity' => 'critical',
                'title' => 'Erreurs critiques detectees',
                'message' => $index['critical_errors_24h'] . ' erreur(s) critiques sur 24h.',
                'url' => $this->routeUrl('logger.index', ['level' => 'critical']),
                'permission' => 'module.logger.list',
            ];
        }

        if ($index['failed_jobs'] > 0) {
            $alerts[] = [
                'severity' => 'warning',
                'title' => 'Queue en echec',
                'message' => $index['failed_jobs'] . ' job(s) failed en attente.',
                'url' => $this->routeUrl('queue.index'),
                'permission' => 'module.queue.list',
            ];
        }

        if ($index['webhooks_failed_24h'] > 0) {
            $alerts[] = [
                'severity' => 'warning',
                'title' => 'Webhooks instables',
                'message' => $index['webhooks_failed_24h'] . ' delivery(s) KO sur 24h.',
                'url' => $this->routeUrl('webhooks.index'),
                'permission' => 'module.webhooks.list',
            ];
        }

        if ($index['low_stock_products'] > 0) {
            $alerts[] = [
                'severity' => 'warning',
                'title' => 'Stock bas detecte',
                'message' => $index['low_stock_products'] . ' produit(s) sous seuil.',
                'url' => $this->routeUrl('shop.manage'),
                'permission' => 'module.shop.list',
            ];
        }

        return $alerts;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function quickActions(): array
    {
        return [
            [
                'label' => 'Nouvelle page',
                'icon' => 'bi bi-plus-square',
                'url' => $this->routeUrl('pages.create'),
                'permission' => 'module.pages.create',
            ],
            [
                'label' => 'Nouvel article',
                'icon' => 'bi bi-journal-plus',
                'url' => $this->routeUrl('articles.create'),
                'permission' => 'module.articles.create',
            ],
            [
                'label' => 'Ajouter media',
                'icon' => 'bi bi-cloud-upload',
                'url' => $this->routeUrl('media.create'),
                'permission' => 'module.media.create',
            ],
            [
                'label' => 'Voir incidents',
                'icon' => 'bi bi-shield-exclamation',
                'url' => $this->routeUrl('logger.alerts.index'),
                'permission' => 'module.logger.list',
            ],
            [
                'label' => 'Ouvrir logs',
                'icon' => 'bi bi-list-ul',
                'url' => $this->routeUrl('logger.index'),
                'permission' => 'module.logger.list',
            ],
            [
                'label' => 'Voir modules',
                'icon' => 'bi bi-puzzle',
                'url' => $this->routeUrl('modules.index'),
                'permission' => 'module.core.list',
            ],
            [
                'label' => 'Ouvrir queue',
                'icon' => 'bi bi-hourglass-split',
                'url' => $this->routeUrl('queue.index'),
                'permission' => 'module.queue.list',
            ],
            [
                'label' => 'Documentation',
                'icon' => 'bi bi-life-preserver',
                'url' => $this->routeUrl('docs.index'),
                'permission' => 'module.docs.list',
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function moduleHealthRows(): array
    {
        $criticalModules = [
            'logger' => 'Logs',
            'queue' => 'Queue',
            'webhooks' => 'Webhooks',
            'mailer' => 'Mailer',
            'shop' => 'Shop',
        ];

        $rows = [];

        foreach ($criticalModules as $slug => $label) {
            $enabled = ModuleManager::isEnabled($slug);
            $rows[] = [
                'label' => $label,
                'status' => $enabled ? 'active' : 'inactive',
                'text' => $enabled ? 'Actif' : 'Inactif',
            ];
        }

        return $rows;
    }

    /**
     * @param array<string, int> $index
     * @return array<int, array<string, mixed>>
     */
    private function defaultWidgets(array $index): array
    {
        return [
            [
                'id' => 'critical-incidents',
                'title' => 'Derniers incidents critiques',
                'subtitle' => 'Alertes non acquittees',
                'order' => 10,
                'tone' => 'danger',
                'items' => $this->recentCriticalIncidents(),
                'empty' => 'Aucun incident critique ouvert.',
                'action' => [
                    'label' => 'Ouvrir alertes',
                    'url' => $this->routeUrl('logger.alerts.index', ['state' => 'open']),
                    'permission' => 'module.logger.list',
                ],
            ],
            [
                'id' => 'failed-jobs',
                'title' => 'Failed jobs recents',
                'subtitle' => 'Execution asynchrone a surveiller',
                'order' => 20,
                'tone' => $index['failed_jobs'] > 0 ? 'warning' : 'secondary',
                'items' => $this->recentFailedJobs(),
                'empty' => 'Aucun job failed.',
                'action' => [
                    'label' => 'Ouvrir queue',
                    'url' => $this->routeUrl('queue.index'),
                    'permission' => 'module.queue.list',
                ],
            ],
            [
                'id' => 'webhooks-failed',
                'title' => 'Webhooks en erreur',
                'subtitle' => 'Dernieres deliveries KO/retrying',
                'order' => 30,
                'tone' => $index['webhooks_failed_24h'] > 0 ? 'warning' : 'secondary',
                'items' => $this->recentWebhookFailures(),
                'empty' => 'Aucune erreur webhook recente.',
                'action' => [
                    'label' => 'Voir webhooks',
                    'url' => $this->routeUrl('webhooks.index'),
                    'permission' => 'module.webhooks.list',
                ],
            ],
            [
                'id' => 'shop-orders',
                'title' => 'Commandes recentes',
                'subtitle' => 'Activite business immediate',
                'order' => 40,
                'tone' => 'info',
                'items' => $this->recentOrders(),
                'empty' => 'Aucune commande recente.',
                'action' => [
                    'label' => 'Voir commandes',
                    'url' => $this->routeUrl('shop.orders.index'),
                    'permission' => 'module.shop.list',
                ],
            ],
            [
                'id' => 'recent-content',
                'title' => 'Derniers contenus modifies',
                'subtitle' => 'Pages + Articles',
                'order' => 50,
                'tone' => 'secondary',
                'items' => $this->recentContentUpdates(),
                'empty' => 'Aucune modification recente.',
                'action' => [
                    'label' => 'Ouvrir contenu',
                    'url' => $this->routeUrl('pages.manage'),
                    'permission' => 'module.pages.list',
                ],
            ],
            [
                'id' => 'low-stock',
                'title' => 'Alertes stock bas',
                'subtitle' => 'Produits sous seuil',
                'order' => 60,
                'tone' => $index['low_stock_products'] > 0 ? 'warning' : 'secondary',
                'items' => $this->lowStockItems(),
                'empty' => 'Aucune alerte stock bas.',
                'action' => [
                    'label' => 'Voir catalogue',
                    'url' => $this->routeUrl('shop.manage'),
                    'permission' => 'module.shop.list',
                ],
            ],
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function recentCriticalIncidents(): array
    {
        if (!$this->hasColumns('system_alerts', ['id', 'title', 'severity', 'alert_type', 'created_at', 'acknowledged'])) {
            return [];
        }

        return DB::table('system_alerts')
            ->where('acknowledged', false)
            ->whereIn('severity', ['critical', 'error', 'warning'])
            ->orderByDesc('id')
            ->limit(6)
            ->get(['title', 'alert_type', 'severity', 'created_at'])
            ->map(function ($row): array {
                return [
                    'primary' => (string) ($row->title ?? 'Incident'),
                    'secondary' => strtoupper((string) ($row->severity ?? 'info')) . ' - ' . (string) ($row->alert_type ?? 'system'),
                    'meta' => $this->formatDate((string) ($row->created_at ?? '')),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function recentFailedJobs(): array
    {
        if (!$this->hasColumns('failed_jobs', ['id', 'queue', 'failed_at'])) {
            return [];
        }

        return DB::table('failed_jobs')
            ->orderByDesc('id')
            ->limit(6)
            ->get(['id', 'queue', 'failed_at'])
            ->map(function ($row): array {
                return [
                    'primary' => 'Job #' . (string) $row->id,
                    'secondary' => 'Queue: ' . (string) ($row->queue ?? 'default'),
                    'meta' => $this->formatDate((string) ($row->failed_at ?? '')),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function recentWebhookFailures(): array
    {
        if (!$this->hasColumns('webhook_deliveries', ['id', 'webhook_id', 'event_type', 'status', 'attempt_number', 'max_attempts', 'created_at'])) {
            return [];
        }

        $query = DB::table('webhook_deliveries as d')
            ->leftJoin('webhooks as w', 'w.id', '=', 'd.webhook_id')
            ->whereIn('d.status', ['failed', 'retrying'])
            ->orderByDesc('d.id')
            ->limit(6)
            ->get([
                'd.event_type',
                'd.status',
                'd.attempt_number',
                'd.max_attempts',
                'd.created_at',
                DB::raw("COALESCE(w.name, 'Webhook') as webhook_name"),
            ]);

        return $query
            ->map(function ($row): array {
                return [
                    'primary' => (string) ($row->webhook_name ?? 'Webhook'),
                    'secondary' => (string) ($row->event_type ?? 'event') . ' - ' . strtoupper((string) ($row->status ?? 'unknown')),
                    'meta' => 'Tentative ' . (int) ($row->attempt_number ?? 0) . '/' . (int) ($row->max_attempts ?? 0) . ' - ' . $this->formatDate((string) ($row->created_at ?? '')),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function recentOrders(): array
    {
        if (!$this->hasColumns('shop_orders', ['id', 'order_number', 'customer_name', 'status', 'grand_total', 'currency', 'created_at'])) {
            return [];
        }

        return DB::table('shop_orders')
            ->orderByDesc('id')
            ->limit(6)
            ->get(['id', 'order_number', 'customer_name', 'status', 'grand_total', 'currency', 'created_at'])
            ->map(function ($row): array {
                $number = (string) ($row->order_number ?? ('CMD-' . (string) $row->id));
                $amount = number_format((float) ($row->grand_total ?? 0), 2, ',', ' ');

                return [
                    'primary' => $number . ' - ' . (string) ($row->customer_name ?? 'Client'),
                    'secondary' => strtoupper((string) ($row->status ?? 'pending')) . ' - ' . $amount . ' ' . strtoupper((string) ($row->currency ?? 'EUR')),
                    'meta' => $this->formatDate((string) ($row->created_at ?? '')),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function recentContentUpdates(): array
    {
        $rows = collect();

        if ($this->hasColumns('pages', ['id', 'title', 'status', 'updated_at'])) {
            $rows = $rows->merge(
                DB::table('pages')
                    ->orderByDesc('updated_at')
                    ->limit(6)
                    ->get(['id', 'title', 'status', 'updated_at'])
                    ->map(fn ($row) => [
                        'primary' => 'Page - ' . (string) ($row->title ?? 'Sans titre'),
                        'secondary' => strtoupper((string) ($row->status ?? 'draft')),
                        'meta' => $this->formatDate((string) ($row->updated_at ?? '')),
                        '_sort_at' => (string) ($row->updated_at ?? ''),
                    ])
            );
        }

        if ($this->hasColumns('articles', ['id', 'title', 'status', 'updated_at'])) {
            $rows = $rows->merge(
                DB::table('articles')
                    ->orderByDesc('updated_at')
                    ->limit(6)
                    ->get(['id', 'title', 'status', 'updated_at'])
                    ->map(fn ($row) => [
                        'primary' => 'Article - ' . (string) ($row->title ?? 'Sans titre'),
                        'secondary' => strtoupper((string) ($row->status ?? 'draft')),
                        'meta' => $this->formatDate((string) ($row->updated_at ?? '')),
                        '_sort_at' => (string) ($row->updated_at ?? ''),
                    ])
            );
        }

        return $rows
            ->sortByDesc(fn (array $row) => (string) ($row['_sort_at'] ?? ''))
            ->take(8)
            ->map(function (array $row): array {
                unset($row['_sort_at']);
                return $row;
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function lowStockItems(): array
    {
        if (!$this->hasColumns('shop_products', ['name', 'sku', 'stock_quantity', 'low_stock_threshold', 'manage_stock'])) {
            return [];
        }

        return DB::table('shop_products')
            ->where('manage_stock', true)
            ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
            ->orderBy('stock_quantity')
            ->limit(6)
            ->get(['name', 'sku', 'stock_quantity', 'low_stock_threshold'])
            ->map(function ($row): array {
                return [
                    'primary' => (string) ($row->name ?? 'Produit'),
                    'secondary' => 'SKU: ' . (string) ($row->sku ?? '-'),
                    'meta' => (int) ($row->stock_quantity ?? 0) . ' / seuil ' . (int) ($row->low_stock_threshold ?? 0),
                ];
            })
            ->values()
            ->all();
    }

    private function countByStatus(string $table, string $status): int
    {
        if (!$this->hasColumns($table, ['status'])) {
            return 0;
        }

        return (int) DB::table($table)->where('status', $status)->count();
    }

    private function countByNotStatus(string $table, string $status): int
    {
        if (!$this->hasColumns($table, ['status'])) {
            return 0;
        }

        return (int) DB::table($table)->where('status', '!=', $status)->count();
    }

    private function countTable(string $table): int
    {
        if (!$this->hasTable($table)) {
            return 0;
        }

        return (int) DB::table($table)->count();
    }

    /**
     * @param array<int, string> $columns
     */
    private function hasColumns(string $table, array $columns): bool
    {
        if (!Schema::hasTable($table)) {
            return false;
        }

        foreach ($columns as $column) {
            if (!Schema::hasColumn($table, $column)) {
                return false;
            }
        }

        return true;
    }

    private function hasTable(string $table): bool
    {
        return Schema::hasTable($table);
    }

    /**
     * @param array<string, mixed> $params
     */
    private function routeUrl(string $name, array $params = []): ?string
    {
        $route = 'admin.' . $name;

        if (!Route::has($route)) {
            return null;
        }

        return route($route, $params);
    }

    private function formatDate(string $value): string
    {
        if ($value === '') {
            return '-';
        }

        try {
            return (string) Carbon::parse($value)->format('d/m H:i');
        } catch (\Throwable) {
            return '-';
        }
    }
}
