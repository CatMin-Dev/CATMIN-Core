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
use App\Services\MonitoringService;
use App\Services\Performance\PerformanceReportService;
use Modules\Cron\Services\CronService;
use Modules\Logger\Services\LogMaintenanceService;
use Modules\Webhooks\Services\WebhookDispatcher;
use Modules\Webhooks\Services\WebhookSecurityService;
use Modules\Webhooks\Models\WebhookDelivery;

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
            $delivery->update(['status' => 'failed']);
            continue;
        }

        WebhookDispatcher::send($delivery->webhook, $delivery->event_type, $delivery->payload ?? []);
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
            $delivery->update(['status' => 'failed']);
            $this->warn('Webhook inactif ou supprimé pour delivery #' . $delivery->id . ' — marqué failed.');
            continue;
        }

        WebhookDispatcher::send($delivery->webhook, $delivery->event_type, $delivery->payload ?? []);
        $this->line('Retry déclenché pour delivery #' . $delivery->id . ' → ' . $delivery->webhook->url);
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
