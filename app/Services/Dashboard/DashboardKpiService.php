<?php

namespace App\Services\Dashboard;

use App\Services\AddonManager;
use App\Services\ModuleManager;
use App\Services\MonitoringService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
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
        $ttl = max(5, (int) config('catmin.performance.dashboard_cache_ttl_seconds', 60));
        $healthOverview = Cache::remember('catmin.dashboard.health-overview.v1', $ttl, fn (): array => app(MonitoringService::class)->buildDashboardReport(5));

        $kpiIndex = Cache::remember('catmin.dashboard.kpi-index.v1', $ttl, function (): array {
            return [
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
                'event_upcoming' => $this->countUpcomingEvents(),
                'event_checkins_today' => $this->countEventCheckinsToday(),
                'incidents_open' => $this->countOpenIncidents(),
            ];
        });

        $kpiIndex['content_total_published'] = (int) ($kpiIndex['pages_published'] ?? 0) + (int) ($kpiIndex['articles_published'] ?? 0);
        $kpiIndex['perf_score'] = (int) (($healthOverview['health_score']['score'] ?? 100));

        return [
            'kpi_index' => $kpiIndex,
            'kpis' => $this->kpiCards($kpiIndex),
            'alerts' => $this->alertRows($kpiIndex, $healthOverview),
            'quick_actions' => $this->quickActions(),
            'module_health' => $this->moduleHealthRows(),
            'widgets' => $this->defaultWidgets($kpiIndex, $healthOverview),
            'charts' => $this->lightweightCharts($healthOverview),
            'health_score' => (array) ($healthOverview['health_score'] ?? []),
            'generated_at' => now(),
            'cache_ttl_seconds' => $ttl,
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

    private function countUpcomingEvents(): int
    {
        if (!$this->hasColumns('events', ['status', 'start_at'])) {
            return 0;
        }

        return (int) DB::table('events')
            ->where('status', 'published')
            ->where('start_at', '>=', now())
            ->count();
    }

    private function countEventCheckinsToday(): int
    {
        if (!$this->hasColumns('event_checkins', ['checkin_at'])) {
            return 0;
        }

        return (int) DB::table('event_checkins')
            ->whereDate('checkin_at', now()->toDateString())
            ->count();
    }

    private function countOpenIncidents(): int
    {
        if (!$this->hasColumns('monitoring_incidents', ['status'])) {
            return 0;
        }

        return (int) DB::table('monitoring_incidents')
            ->whereIn('status', ['warning', 'degraded', 'critical'])
            ->count();
    }

    /**
     * @param array<string, int> $index
     * @return array<int, array<string, mixed>>
     */
    private function kpiCards(array $index): array
    {
        $cards = [
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
                'id' => 'content_total',
                'label' => 'Contenus publies',
                'value' => $index['content_total_published'],
                'description' => 'Pages + articles publies',
                'icon' => 'bi bi-collection',
                'permission' => 'module.pages.list',
                'url' => $this->routeUrl('pages.manage'),
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
                'id' => 'incidents_open',
                'label' => 'Incidents ouverts',
                'value' => $index['incidents_open'],
                'description' => 'Monitoring warning/degraded/critical',
                'icon' => 'bi bi-shield-exclamation',
                'permission' => 'module.logger.list',
                'url' => $this->routeUrl('monitoring.index'),
            ],
            [
                'id' => 'perf_score',
                'label' => 'Score performance',
                'value' => $index['perf_score'] . '/100',
                'description' => 'Score global monitoring',
                'icon' => 'bi bi-speedometer2',
                'permission' => 'module.logger.list',
                'url' => $this->routeUrl('performance.index'),
            ],
            [
                'id' => 'failed_jobs',
                'label' => 'Jobs en echec',
                'value' => $index['failed_jobs'],
                'description' => 'Queue a relancer',
                'icon' => 'bi bi-exclamation-triangle',
                'permission' => 'module.queue.list',
                'url' => $this->routeUrl('queue.index', ['status' => 'failed']),
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
        ];

        if ($this->isAddonEnabled('catmin-shop')) {
            $cards[] = [
                'id' => 'shop_pending',
                'label' => 'Commandes en attente',
                'value' => $index['shop_orders_pending'],
                'description' => 'A traiter rapidement',
                'icon' => 'bi bi-bag-check',
                'permission' => 'module.shop.list',
                'url' => $this->routeUrl('shop.orders.index'),
            ];

            $cards[] = [
                'id' => 'low_stock',
                'label' => 'Produits stock bas',
                'value' => $index['low_stock_products'],
                'description' => 'Seuil mini atteint',
                'icon' => 'bi bi-box-seam',
                'permission' => 'module.shop.list',
                'url' => $this->routeUrl('shop.manage'),
            ];
        }

        if ($this->isAddonEnabled('cat-event')) {
            $cards[] = [
                'id' => 'events_upcoming',
                'label' => 'Events a venir',
                'value' => $index['event_upcoming'],
                'description' => 'Evenements publies non demarres',
                'icon' => 'bi bi-calendar-event',
                'permission' => 'module.events.list',
                'url' => $this->routeUrl('events.index'),
            ];

            $cards[] = [
                'id' => 'events_checkins_today',
                'label' => 'Check-ins du jour',
                'value' => $index['event_checkins_today'],
                'description' => 'Flux check-in en cours',
                'icon' => 'bi bi-qr-code-scan',
                'permission' => 'module.events.checkin',
                'url' => $this->routeUrl('events.index'),
            ];
        }

        return $cards;
    }

    /**
     * @param array<string, int> $index
     * @return array<int, array<string, mixed>>
     */
    private function alertRows(array $index, array $healthOverview): array
    {
        $alerts = [];
        $healthScore = (array) ($healthOverview['health_score'] ?? []);

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
                'url' => $this->routeUrl('queue.index', ['status' => 'failed']),
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

        foreach (array_slice((array) ($healthScore['recommendations'] ?? []), 0, 2) as $recommendation) {
            if (!is_array($recommendation)) {
                continue;
            }

            $alerts[] = [
                'severity' => (string) ($recommendation['severity'] ?? 'warning'),
                'title' => (string) ($recommendation['title'] ?? 'Diagnostic systeme'),
                'message' => (string) ($recommendation['message'] ?? ''),
                'url' => (string) ($recommendation['url'] ?? ''),
                'permission' => null,
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
                'label' => 'Monitoring center',
                'icon' => 'bi bi-activity',
                'url' => $this->routeUrl('monitoring.index'),
                'permission' => 'module.logger.list',
            ],
            [
                'label' => 'Performance center',
                'icon' => 'bi bi-speedometer2',
                'url' => $this->routeUrl('performance.index'),
                'permission' => 'module.logger.list',
            ],
            [
                'label' => 'System check',
                'icon' => 'bi bi-clipboard-check',
                'url' => $this->routeUrl('system.check'),
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
    private function defaultWidgets(array $index, array $healthOverview): array
    {
        $monitoringWidget = $this->monitoringWidget($healthOverview);

        $widgets = [
            $monitoringWidget,
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
                    'url' => $this->routeUrl('queue.index', ['status' => 'failed']),
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
        ];

        if ($this->isAddonEnabled('catmin-shop')) {
            $widgets[] = [
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
            ];

            $widgets[] = [
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
            ];
        }

        if ($this->isAddonEnabled('cat-event')) {
            $widgets[] = [
                'id' => 'event-activity',
                'title' => 'Activite events',
                'subtitle' => 'Sessions, tickets et check-ins',
                'order' => 45,
                'tone' => 'info',
                'items' => $this->recentEventActivity(),
                'empty' => 'Aucune activite event recente.',
                'action' => [
                    'label' => 'Ouvrir events',
                    'url' => $this->routeUrl('events.index'),
                    'permission' => 'module.events.list',
                ],
            ];
        }

        return $widgets;
    }

    /**
     * @param array<string, mixed> $healthOverview
     * @return array<string, mixed>
     */
    private function lightweightCharts(array $healthOverview): array
    {
        return [
            'content_7d' => $this->contentTimelineLastDays(7),
            'incidents_7d' => $this->incidentTimelineLastDays(7),
            'perf_12h' => $this->performanceTimelineLastHours(12, $healthOverview),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function contentTimelineLastDays(int $days): array
    {
        $days = max(3, min(30, $days));
        $series = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $day = now()->subDays($i)->toDateString();
            $series[$day] = [
                'label' => Carbon::parse($day)->format('d/m'),
                'pages' => 0,
                'articles' => 0,
            ];
        }

        if ($this->hasColumns('pages', ['status', 'published_at'])) {
            DB::table('pages')
                ->selectRaw('DATE(published_at) as day, count(*) as total')
                ->where('status', 'published')
                ->where('published_at', '>=', now()->subDays($days - 1)->startOfDay())
                ->groupBy('day')
                ->get()
                ->each(function ($row) use (&$series): void {
                    $day = (string) ($row->day ?? '');
                    if (isset($series[$day])) {
                        $series[$day]['pages'] = (int) ($row->total ?? 0);
                    }
                });
        }

        if ($this->hasColumns('articles', ['status', 'published_at'])) {
            DB::table('articles')
                ->selectRaw('DATE(published_at) as day, count(*) as total')
                ->where('status', 'published')
                ->where('published_at', '>=', now()->subDays($days - 1)->startOfDay())
                ->groupBy('day')
                ->get()
                ->each(function ($row) use (&$series): void {
                    $day = (string) ($row->day ?? '');
                    if (isset($series[$day])) {
                        $series[$day]['articles'] = (int) ($row->total ?? 0);
                    }
                });
        }

        return array_values($series);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function incidentTimelineLastDays(int $days): array
    {
        $days = max(3, min(30, $days));
        $series = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $day = now()->subDays($i)->toDateString();
            $series[$day] = [
                'label' => Carbon::parse($day)->format('d/m'),
                'count' => 0,
            ];
        }

        if (!$this->hasColumns('system_alerts', ['severity', 'created_at'])) {
            return array_values($series);
        }

        DB::table('system_alerts')
            ->selectRaw('DATE(created_at) as day, count(*) as total')
            ->whereIn('severity', ['warning', 'error', 'critical', 'alert', 'emergency'])
            ->where('created_at', '>=', now()->subDays($days - 1)->startOfDay())
            ->groupBy('day')
            ->get()
            ->each(function ($row) use (&$series): void {
                $day = (string) ($row->day ?? '');
                if (isset($series[$day])) {
                    $series[$day]['count'] = (int) ($row->total ?? 0);
                }
            });

        return array_values($series);
    }

    /**
     * @param array<string, mixed> $healthOverview
     * @return array<int, array<string, mixed>>
     */
    private function performanceTimelineLastHours(int $hours, array $healthOverview): array
    {
        $hours = max(6, min(48, $hours));
        $series = [];

        for ($i = $hours - 1; $i >= 0; $i--) {
            $point = now()->subHours($i);
            $key = $point->format('Y-m-d H:00:00');
            $series[$key] = [
                'label' => $point->format('H:i'),
                'score' => null,
            ];
        }

        if ($this->hasColumns('monitoring_snapshots', ['score', 'created_at'])) {
            $bucket = [];

            DB::table('monitoring_snapshots')
                ->where('created_at', '>=', now()->subHours($hours - 1))
                ->orderBy('created_at')
                ->get(['score', 'created_at'])
                ->each(function ($row) use (&$bucket): void {
                    $rawDate = (string) ($row->created_at ?? '');
                    if ($rawDate === '') {
                        return;
                    }

                    try {
                        $hourKey = Carbon::parse($rawDate)->format('Y-m-d H:00:00');
                    } catch (\Throwable) {
                        return;
                    }

                    if (!isset($bucket[$hourKey])) {
                        $bucket[$hourKey] = ['sum' => 0, 'count' => 0];
                    }

                    $bucket[$hourKey]['sum'] += (int) ($row->score ?? 0);
                    $bucket[$hourKey]['count'] += 1;
                });

            foreach ($bucket as $hourKey => $aggregate) {
                if (!isset($series[$hourKey])) {
                    continue;
                }

                $count = max(1, (int) ($aggregate['count'] ?? 0));
                $sum = (int) ($aggregate['sum'] ?? 0);
                $series[$hourKey]['score'] = (int) round($sum / $count);
            }
        }

        $fallback = (int) (($healthOverview['health_score']['score'] ?? 100));
        foreach ($series as $key => $row) {
            if ($row['score'] === null) {
                $series[$key]['score'] = $fallback;
            }
        }

        return array_values($series);
    }

    /**
     * @return array<string, mixed>
     */
    private function monitoringWidget(array $report): array
    {
        $global = (array) ($report['global'] ?? []);
        $health = (array) ($report['health_score'] ?? []);
        $status = (string) ($global['status'] ?? 'ok');

        $tone = match ($status) {
            'critical' => 'danger',
            'degraded' => 'warning',
            'warning' => 'info',
            default => 'secondary',
        };

        $items = [
            [
                'primary' => 'Score global: ' . (int) ($health['score'] ?? ($global['score'] ?? 100)) . '/100 - ' . (string) ($health['label'] ?? 'Stable'),
                'secondary' => 'Statut operationnel: ' . strtoupper($status),
                'meta' => 'Confiance ' . (int) ($health['confidence'] ?? 100) . '%',
            ],
            [
                'primary' => 'Incidents ouverts',
                'secondary' => (string) collect((array) ($report['incidents'] ?? []))->count(),
                'meta' => 'Inclut warning/degraded/critical',
            ],
        ];

        $firstRecommendation = collect((array) ($health['recommendations'] ?? []))->first();
        if (is_array($firstRecommendation)) {
            $items[] = [
                'primary' => 'Priorite: ' . (string) ($firstRecommendation['title'] ?? 'Action corrective'),
                'secondary' => (string) ($firstRecommendation['message'] ?? ''),
                'meta' => 'Penalite ' . (int) ($firstRecommendation['penalty'] ?? 0) . ' pts',
            ];
        }

        $trend = (array) ($health['trend'] ?? []);
        $items[] = [
            'primary' => 'Tendance',
            'secondary' => (string) ($trend['message'] ?? 'Aucune tendance exploitable.'),
            'meta' => 'Delta ' . ((int) ($trend['delta'] ?? 0) >= 0 ? '+' : '') . (int) ($trend['delta'] ?? 0),
        ];

        return [
            'id' => 'monitoring-overview',
            'title' => 'Sante globale systeme',
            'subtitle' => 'Score, tendance et recommandations prioritaires',
            'order' => 5,
            'tone' => $tone,
            'items' => $items,
            'empty' => 'Aucune donnee monitoring.',
            'action' => [
                'label' => 'Ouvrir monitoring',
                'url' => $this->routeUrl('monitoring.index'),
                'permission' => 'module.logger.list',
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

    /**
     * @return array<int, array<string, string>>
     */
    private function recentEventActivity(): array
    {
        $items = [];

        if ($this->hasColumns('events', ['title', 'start_at', 'status'])) {
            $events = DB::table('events')
                ->where('start_at', '>=', now()->subDay())
                ->orderBy('start_at')
                ->limit(4)
                ->get(['title', 'start_at', 'status']);

            foreach ($events as $event) {
                $items[] = [
                    'primary' => (string) ($event->title ?? 'Event'),
                    'secondary' => 'EVENT ' . strtoupper((string) ($event->status ?? 'draft')),
                    'meta' => $this->formatDate((string) ($event->start_at ?? '')),
                ];
            }
        }

        if ($this->hasColumns('event_checkins', ['checkin_at']) && count($items) < 6) {
            $checkins = (int) DB::table('event_checkins')
                ->whereDate('checkin_at', now()->toDateString())
                ->count();

            $items[] = [
                'primary' => 'Check-ins aujourd\'hui',
                'secondary' => (string) $checkins,
                'meta' => now()->format('d/m H:i'),
            ];
        }

        return collect($items)->take(6)->values()->all();
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

    protected function isAddonEnabled(string $slug): bool
    {
        $normalized = strtolower($slug);

        return AddonManager::enabled()->contains(function ($addon) use ($normalized): bool {
            return strtolower((string) ($addon->slug ?? '')) === $normalized;
        });
    }
}
