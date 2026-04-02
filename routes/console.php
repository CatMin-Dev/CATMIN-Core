<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduler (Cron module — 058)
|--------------------------------------------------------------------------
| These tasks run via: php artisan schedule:run
| Crontab: * * * * * cd /path/to/catmin && php artisan schedule:run >> /dev/null 2>&1
*/

use Illuminate\Support\Facades\Schedule;
use App\Services\Content\ScheduledContentPublisher;
use App\Services\SettingService;
use App\Services\MonitoringService;
use App\Services\Performance\PerformanceReportService;
use Modules\Cron\Services\CronService;
use Modules\Logger\Services\LogMaintenanceService;
use Modules\Webhooks\Services\WebhookDispatcher;
use Modules\Webhooks\Services\WebhookSecurityService;
use Modules\Webhooks\Models\WebhookDelivery;
use Modules\Pages\Services\PagesAdminService;
use Modules\Articles\Services\ArticleAdminService;
use Modules\Media\Services\MediaAdminService;
use Modules\SEO\Services\SitemapService;

Schedule::call(function (): void {
    CronService::runTask('cache.clear');
})->daily()->name('cron.cache-clear')->withoutOverlapping();

Schedule::call(function (): void {
    CronService::runTask('queue.prune');
})->weekly()->name('cron.queue-prune')->withoutOverlapping();

Schedule::call(function (): void {
    CronService::runDueCustomTasks();
})->everyMinute()->name('cron.custom-tasks')->withoutOverlapping();

Schedule::call(function (): void {
    $svc = app(LogMaintenanceService::class);
    $svc->rotateDaily($svc->resolvedRetentionDays(), $svc->resolvedArchiveRetentionDays());
})->dailyAt('02:30')->name('logger.rotate-daily')->withoutOverlapping();

Schedule::call(function (): void {
    app(MonitoringService::class)->captureSnapshot();
})->everyFiveMinutes()->name('monitoring.snapshot')->withoutOverlapping();

Schedule::call(function (): void {
    app(MonitoringService::class)->pruneSnapshots(30);
})->dailyAt('03:30')->name('monitoring.prune')->withoutOverlapping();

// Clean up expired webhook nonces daily
Schedule::call(function (): void {
    app(WebhookSecurityService::class)->cleanupExpiredNonces();
    app(WebhookSecurityService::class)->cleanupOldEvents(30);
})->dailyAt('03:00')->name('webhooks.cleanup')->withoutOverlapping();

Schedule::command('catmin:content:publish-scheduled')
    ->everyMinute()
    ->name('content.publish-scheduled')
    ->withoutOverlapping();

Schedule::call(function (): void {
    $days = max(1, (int) SettingService::get('content.trash_retention_days', config('catmin.content.trash_retention_days', 30)));
    Artisan::call('catmin:pages:purge-trash', ['--days' => $days]);
})->dailyAt('04:15')->name('pages.purge-trash')->withoutOverlapping();

Schedule::call(function (): void {
    $days = max(1, (int) SettingService::get('content.trash_retention_days', config('catmin.content.trash_retention_days', 30)));
    Artisan::call('catmin:articles:purge-trash', ['--days' => $days]);
})->dailyAt('04:25')->name('articles.purge-trash')->withoutOverlapping();

Schedule::call(function (): void {
    $days = max(1, (int) SettingService::get('content.trash_retention_days', config('catmin.content.trash_retention_days', 30)));
    Artisan::call('catmin:media:purge-trash', ['--days' => $days]);
})->dailyAt('04:35')->name('media.purge-trash')->withoutOverlapping();

Schedule::call(function (): void {
    $enabled = filter_var(SettingService::get('seo.sitemap_auto_refresh', true), FILTER_VALIDATE_BOOL);
    if (!$enabled) {
        return;
    }

    app(SitemapService::class)->refresh();
})->dailyAt('04:45')->name('seo.sitemap.refresh')->withoutOverlapping();

Artisan::command('catmin:content:publish-scheduled', function () {
    $result = app(ScheduledContentPublisher::class)->publishDue();

    $this->info('Publication differée traitee.');
    $this->line('Pages publiees: ' . (int) ($result['pages'] ?? 0));
    $this->line('Articles publies: ' . (int) ($result['articles'] ?? 0));
    $this->line('Total: ' . (int) ($result['total'] ?? 0));
})->purpose('Publish scheduled pages and articles when published_at is due');

Artisan::command('catmin:pages:purge-trash {--days=30}', function () {
    $days = max(1, (int) $this->option('days'));
    $count = app(PagesAdminService::class)->purgeTrashOlderThan($days);

    $this->info('Corbeille pages purgee.');
    $this->line('Retention (jours): ' . $days);
    $this->line('Pages supprimees definitivement: ' . $count);
})->purpose('Purge soft-deleted pages older than retention days');

Artisan::command('catmin:articles:purge-trash {--days=30}', function () {
    $days = max(1, (int) $this->option('days'));
    $count = app(ArticleAdminService::class)->purgeTrashOlderThan($days);

    $this->info('Corbeille articles purgee.');
    $this->line('Retention (jours): ' . $days);
    $this->line('Articles supprimes definitivement: ' . $count);
})->purpose('Purge soft-deleted articles older than retention days');

Artisan::command('catmin:media:purge-trash {--days=30}', function () {
    $days = max(1, (int) $this->option('days'));
    $count = app(MediaAdminService::class)->purgeTrashOlderThan($days);

    $this->info('Corbeille media purgee.');
    $this->line('Retention (jours): ' . $days);
    $this->line('Medias supprimes definitivement: ' . $count);
})->purpose('Purge soft-deleted media assets older than retention days');

Artisan::command('catmin:seo:sitemap:refresh', function () {
    app(SitemapService::class)->refresh();
    $this->info('Sitemap regenere avec succes.');
})->purpose('Refresh cached sitemap.xml');

Artisan::command('catmin:logs:rotate', function () {
    $svc = app(LogMaintenanceService::class);
    $result = $svc->rotateDaily($svc->resolvedRetentionDays(), $svc->resolvedArchiveRetentionDays());

    $this->info('Rotation logs terminée.');
    $this->line('Archivé: ' . (int) ($result['archived'] ?? 0));
    $this->line('Purgé (archive): ' . (int) ($result['purged_archive'] ?? 0));
})->purpose('Rotate and archive system logs according to CATMIN retention policies');

// ─── Webhook retry loop ─────────────────────────────────────────────────────
Schedule::call(function (): void {
    $retrying = WebhookDelivery::query()
        ->where('status', 'retrying')
        ->where('next_retry_at', '<=', now())
        ->with('webhook')
        ->limit(50)
        ->get();

    foreach ($retrying as $delivery) {
        if (!$delivery->webhook || $delivery->webhook->status !== 'active') {
            $delivery->markFailed('Webhook not found or inactive');
            continue;
        }

        WebhookDispatcher::retryDelivery($delivery);
    }
})->everyFiveMinutes()->name('webhooks.process-retries')->withoutOverlapping();

Artisan::command('catmin:webhooks:retry', function () {
    $retrying = WebhookDelivery::query()
        ->where('status', 'retrying')
        ->where('next_retry_at', '<=', now())
        ->with('webhook')
        ->get();

    if ($retrying->isEmpty()) {
        $this->info('Aucune livraison en attente de retry.');
        return;
    }

    $this->info('Livraisons en attente: ' . $retrying->count());

    foreach ($retrying as $delivery) {
        if (!$delivery->webhook || $delivery->webhook->status !== 'active') {
            $delivery->markFailed('Webhook not found or inactive');
            $this->warn('Webhook inactif ou supprimé pour delivery #' . $delivery->id . ' — marqué failed.');
            continue;
        }

        WebhookDispatcher::retryDelivery($delivery);
        $delivery->refresh();
        $this->line('Retry delivery #' . $delivery->id . ' → ' . $delivery->webhook->url . ' [' . $delivery->status . ']');
    }

    $this->info('Retry terminé.');
})->purpose('Process retryable failed webhook deliveries');

Artisan::command('catmin:webhooks:cleanup', function () {
    $nonces = app(WebhookSecurityService::class)->cleanupExpiredNonces();
    $events = app(WebhookSecurityService::class)->cleanupOldEvents(30);
    $this->info("Nonces expirés supprimés: $nonces");
    $this->info("Événements anciens supprimés: $events");
})->purpose('Clean up expired webhook nonces and old event records');

Artisan::command('catmin:performance:report {--hours=24} {--json} {--save}', function () {
    $hours = max(1, min(168, (int) $this->option('hours')));
    $report = app(PerformanceReportService::class)->buildReport($hours);

    if ($this->option('save')) {
        $paths = app(PerformanceReportService::class)->saveReport($report);
        $this->info('Rapport performance sauvegarde.');
        $this->line('JSON: ' . $paths['json']);
        $this->line('Markdown: ' . $paths['markdown']);
    }

    if ($this->option('json')) {
        $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        return;
    }

    $summary = (array) ($report['summary'] ?? []);
    $this->info('CATMIN Performance Report');
    $this->line('Window: ' . $hours . 'h');
    $this->line('Requests profiled: ' . (int) ($summary['requests_profiled'] ?? 0));
    $this->line('Slow requests: ' . (int) ($summary['slow_requests'] ?? 0));
    $this->line('Budget breaches: ' . (int) ($summary['budget_breaches'] ?? 0));
    $this->line('Slow queries: ' . (int) ($summary['slow_queries'] ?? 0));
    $this->line('Long jobs: ' . (int) ($summary['long_jobs'] ?? 0));
})->purpose('Generate a CATMIN performance profiling report');
