<?php

namespace App\Services\Notifications;

use App\Services\SecurityHardeningService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Logger\Models\MonitoringIncident;
use Modules\Logger\Models\SystemAlert;
use Modules\Logger\Models\SystemLog;
use Modules\Notifications\Models\AdminNotification;

/**
 * Aggregation service — bridges existing system signals to the admin notification layer.
 * Called from cron or event listeners to avoid N+1 fan-outs during hot paths.
 */
class NotificationAggregationService
{
    /**
     * Aggregate all system signals into admin notifications.
     * Safe to run repeatedly (dedup prevents duplicates).
     */
    public static function aggregate(): void
    {
        try {
            static::fromCriticalLogs();
        } catch (\Throwable $e) {
            Log::warning('NotificationAggregation: critical logs failed', ['error' => $e->getMessage()]);
        }

        try {
            static::fromOpenIncidents();
        } catch (\Throwable $e) {
            Log::warning('NotificationAggregation: monitoring incidents failed', ['error' => $e->getMessage()]);
        }

        try {
            static::fromSystemAlerts();
        } catch (\Throwable $e) {
            Log::warning('NotificationAggregation: system alerts failed', ['error' => $e->getMessage()]);
        }

        try {
            static::fromFailedJobs();
        } catch (\Throwable $e) {
            Log::warning('NotificationAggregation: failed jobs failed', ['error' => $e->getMessage()]);
        }

        try {
            static::fromWebhookFailures();
        } catch (\Throwable $e) {
            Log::warning('NotificationAggregation: webhook failures failed', ['error' => $e->getMessage()]);
        }

        try {
            static::fromSecurityChecks();
        } catch (\Throwable $e) {
            Log::warning('NotificationAggregation: security checks failed', ['error' => $e->getMessage()]);
        }

        try {
            static::fromMailerFailures();
        } catch (\Throwable $e) {
            Log::warning('NotificationAggregation: mailer failures failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Safely resolve a route URL without throwing in tests.
     */
    private static function routeUrl(string $name, array $params = []): ?string
    {
        try {
            return route($name, $params);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Promote recent critical/emergency log entries.
     */
    public static function fromCriticalLogs(): void
    {
        if (!class_exists(SystemLog::class)) {
            return;
        }

        $logs = SystemLog::query()
            ->whereIn('level', ['critical', 'emergency'])
            ->where('created_at', '>=', now()->subHour())
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        foreach ($logs as $log) {
            $key = 'log.critical.' . md5((string) ($log->event ?? '') . '.' . (string) ($log->message ?? ''));

            AdminNotificationService::notify(
                title: 'Erreur critique: ' . substr((string) ($log->event ?? 'unknown'), 0, 100),
                message: substr((string) ($log->message ?? ''), 0, 500),
                type: 'critical',
                source: 'system',
                actionUrl: self::routeUrl('admin.logger.index', ['level' => 'critical']),
                actionLabel: 'Voir les logs',
                context: ['log_id' => $log->id, 'channel' => $log->channel],
                dedupeKey: $key,
            );
        }
    }

    /**
     * Promote open critical/degraded monitoring incidents.
     */
    public static function fromOpenIncidents(): void
    {
        if (!class_exists(MonitoringIncident::class)) {
            return;
        }

        $incidents = MonitoringIncident::query()
            ->whereIn('status', ['critical', 'degraded'])
            ->where('last_seen_at', '>=', now()->subHours(2))
            ->orderByDesc('last_seen_at')
            ->limit(10)
            ->get();

        foreach ($incidents as $incident) {
            $key = 'monitoring.incident.' . (string) ($incident->domain ?? '') . '.' . (string) ($incident->title ?? '');
            $key = 'monitoring.incident.' . md5($key);
            $type = $incident->status === 'critical' ? 'critical' : 'warning';

            AdminNotificationService::notify(
                title: 'Incident ' . strtoupper((string) $incident->status) . ': ' . substr((string) ($incident->title ?? 'Incident'), 0, 100),
                message: substr((string) ($incident->message ?? ''), 0, 500),
                type: $type,
                source: 'monitoring',
                actionUrl: self::routeUrl('admin.monitoring.index'),
                actionLabel: 'Voir le monitoring',
                context: ['incident_id' => $incident->id, 'domain' => $incident->domain],
                dedupeKey: $key,
            );
        }
    }

    /**
     * Promote unacknowledged system alerts to notifications.
     */
    public static function fromSystemAlerts(): void
    {
        if (!class_exists(SystemAlert::class)) {
            return;
        }

        $alerts = SystemAlert::query()
            ->where('acknowledged', false)
            ->where('created_at', '>=', now()->subHours(2))
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        foreach ($alerts as $alert) {
            $key = 'alert.' . md5((string) ($alert->alert_type ?? '') . '.' . (string) ($alert->title ?? ''));
            $type = $alert->severity === 'critical' ? 'critical' : 'warning';

            AdminNotificationService::notify(
                title: 'Alerte système: ' . substr((string) ($alert->title ?? ''), 0, 100),
                message: substr((string) ($alert->message ?? ''), 0, 500),
                type: $type,
                source: 'monitoring',
                actionUrl: self::routeUrl('admin.monitoring.index'),
                actionLabel: 'Voir le monitoring',
                context: ['alert_id' => $alert->id, 'alert_type' => $alert->alert_type],
                dedupeKey: $key,
            );
        }
    }

    /**
     * Check failed_jobs table for recent failures.
     */
    public static function fromFailedJobs(): void
    {
        if (!DB::getSchemaBuilder()->hasTable('failed_jobs')) {
            return;
        }

        $count = (int) DB::table('failed_jobs')
            ->where('failed_at', '>=', now()->subHour())
            ->count();

        if ($count === 0) {
            return;
        }

        $window = now()->subHour()->format('Y-m-d H:i');
        $key = 'queue.failed.' . md5($window . '.' . $count);

        AdminNotificationService::notify(
            title: "{$count} job(s) en échec dans la dernière heure",
            message: "Des jobs ont échoué récemment dans la file d'attente. Consultez le panneau Queue pour les détails.",
            type: $count >= 5 ? 'critical' : 'warning',
            source: 'queue',
            actionUrl: self::routeUrl('admin.queue.index'),
            actionLabel: 'Voir la queue',
            context: ['failed_count' => $count],
            dedupeKey: $key,
        );
    }

    /**
     * Check for many consecutive webhook delivery failures.
     */
    public static function fromWebhookFailures(): void
    {
        if (!DB::getSchemaBuilder()->hasTable('webhook_deliveries')) {
            return;
        }

        $failureCount = (int) DB::table('webhook_deliveries')
            ->where('status', 'failed')
            ->where('created_at', '>=', now()->subHour())
            ->count();

        if ($failureCount < 3) {
            return;
        }

        $window = now()->subHour()->format('Y-m-d H:i');
        $key = 'webhooks.failures.' . md5($window . '.' . $failureCount);

        AdminNotificationService::notify(
            title: "{$failureCount} échec(s) de livraison webhook dans la dernière heure",
            message: "Les livraisons webhook enregistrent un taux d'échec élevé. Vérifiez les endpoints et la configuration.",
            type: $failureCount >= 10 ? 'critical' : 'warning',
            source: 'webhooks',
            actionUrl: self::routeUrl('admin.webhooks.index'),
            actionLabel: 'Voir les webhooks',
            context: ['failure_count' => $failureCount],
            dedupeKey: $key,
        );
    }

    /**
     * Run security hardening checks and promote critical issues.
     */
    public static function fromSecurityChecks(): void
    {
        if (!class_exists(SecurityHardeningService::class)) {
            return;
        }

        try {
            $service = app(SecurityHardeningService::class);
            $checks = (array) $service->runAllChecks();

            foreach ($checks as $check) {
                if (!is_array($check)) {
                    continue;
                }

                $status = (string) ($check['status'] ?? 'ok');
                if (!in_array($status, ['critical', 'warning'], true)) {
                    continue;
                }

                $checkKey = (string) ($check['key'] ?? '');
                if ($checkKey === '') {
                    continue;
                }

                $key = 'security.' . md5($checkKey . $status);
                $type = $status === 'critical' ? 'critical' : 'warning';

                AdminNotificationService::notify(
                    title: 'Sécurité: ' . substr((string) ($check['label'] ?? $checkKey), 0, 100),
                    message: substr((string) ($check['recommendation'] ?? $check['detail'] ?? 'Vérifiez la configuration de sécurité.'), 0, 500),
                    type: $type,
                    source: 'security',
                    actionUrl: self::routeUrl('admin.settings.index'),
                    actionLabel: 'Voir les paramètres',
                    context: ['check_key' => $checkKey, 'status' => $status],
                    dedupeKey: $key,
                );
            }
        } catch (\Throwable $e) {
            Log::warning('NotificationAggregation: security check threw', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Check mailer history for recent failures.
     */
    public static function fromMailerFailures(): void
    {
        if (!DB::getSchemaBuilder()->hasTable('mailer_history')) {
            return;
        }

        $failCount = (int) DB::table('mailer_history')
            ->where('status', 'failed')
            ->where('created_at', '>=', now()->subHour())
            ->count();

        if ($failCount < 3) {
            return;
        }

        $window = now()->subHour()->format('Y-m-d H:i');
        $key = 'mailer.failures.' . md5($window . '.' . $failCount);

        AdminNotificationService::notify(
            title: "{$failCount} email(s) en échec dans la dernière heure",
            message: "Des envois d'emails ont échoué récemment. Vérifiez la configuration mailer et les logs.",
            type: $failCount >= 10 ? 'critical' : 'warning',
            source: 'mailer',
            actionUrl: self::routeUrl('admin.mailer.manage'),
            actionLabel: 'Voir le mailer',
            context: ['fail_count' => $failCount],
            dedupeKey: $key,
        );
    }
}
