<?php

namespace Module\Logger\Services;

use Module\Logger\Models\SystemAlert;
use Illuminate\Support\Facades\Log;

class AlertingService
{
    /**
     * Alert types and their default severity
     */
    private const ALERT_TYPES = [
        'webhook_failed' => 'critical',
        'webhook_retrying' => 'warning',
        'job_failed' => 'critical',
        'critical_error' => 'critical',
        'health_check_failed' => 'warning',
        'queue_stalled' => 'critical',
        'db_connection_failed' => 'critical',
        'memory_threshold_exceeded' => 'warning',
        'disk_space_low' => 'critical',
    ];

    /**
     * Create a new system alert
     */
    public function createAlert(
        string $type,
        string $title,
        string $message,
        array $context = [],
        ?string $severity = null
    ): SystemAlert {
        $severity = $severity ?? self::ALERT_TYPES[$type] ?? 'warning';

        $alert = SystemAlert::create([
            'alert_type' => $type,
            'severity' => $severity,
            'title' => $title,
            'message' => $message,
            'context' => $context,
        ]);

        Log::warning('System alert created', [
            'alert_id' => $alert->id,
            'type' => $type,
            'severity' => $severity,
        ]);

        return $alert;
    }

    /**
     * Record webhook failure alert
     */
    public function alertWebhookFailed(
        int $webhookId,
        string $url,
        string $errorMessage,
        ?int $responseCode = null
    ): SystemAlert {
        return $this->createAlert(
            'webhook_failed',
            "Webhook Delivery Failed: $url",
            "Failed to deliver webhook to $url: $errorMessage",
            [
                'webhook_id' => $webhookId,
                'url' => $url,
                'error' => $errorMessage,
                'response_code' => $responseCode,
            ],
            'critical'
        );
    }

    /**
     * Record webhook retry alert
     */
    public function alertWebhookRetrying(
        int $webhookId,
        string $url,
        int $attempt,
        int $maxAttempts
    ): SystemAlert {
        return $this->createAlert(
            'webhook_retrying',
            "Webhook Retry: $url",
            "Webhook delivery to $url is retrying (attempt $attempt of $maxAttempts)",
            [
                'webhook_id' => $webhookId,
                'url' => $url,
                'attempt' => $attempt,
                'max_attempts' => $maxAttempts,
            ],
            'warning'
        );
    }

    /**
     * Record job failure alert
     */
    public function alertJobFailed(
        string $jobName,
        string $errorMessage,
        array $context = []
    ): SystemAlert {
        return $this->createAlert(
            'job_failed',
            "Job Failed: $jobName",
            "Job $jobName failed: $errorMessage",
            array_merge([
                'job_name' => $jobName,
                'error' => $errorMessage,
            ], $context),
            'critical'
        );
    }

    /**
     * Record critical error alert
     */
    public function alertCriticalError(
        string $errorMessage,
        string $file,
        int $line,
        array $context = []
    ): SystemAlert {
        return $this->createAlert(
            'critical_error',
            'Critical Error Detected',
            "$errorMessage at $file:$line",
            array_merge([
                'file' => $file,
                'line' => $line,
                'error' => $errorMessage,
            ], $context),
            'critical'
        );
    }

    /**
     * Record health check failure alert
     */
    public function alertHealthCheckFailed(
        string $checkName,
        string $reason,
        array $details = []
    ): SystemAlert {
        return $this->createAlert(
            'health_check_failed',
            "Health Check Failed: $checkName",
            "Health check $checkName failed: $reason",
            array_merge([
                'check' => $checkName,
                'reason' => $reason,
            ], $details),
            'warning'
        );
    }

    /**
     * Get unacknowledged critical alerts
     */
    public function getUnacknowledgedCriticalAlerts(int $limit = 10): array
    {
        return SystemAlert::critical()
            ->unacknowledged()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get recent alerts summary for dashboard
     */
    public function getRecentAlertsSummary(int $hours = 24): array
    {
        $alerts = SystemAlert::where('created_at', '>=', now()->subHours($hours))
            ->get();

        $summary = [
            'total' => $alerts->count(),
            'unacknowledged' => $alerts->where('acknowledged', false)->count(),
            'critical' => $alerts->where('severity', 'critical')->count(),
            'warning' => $alerts->where('severity', 'warning')->count(),
            'info' => $alerts->where('severity', 'info')->count(),
            'by_type' => $alerts->groupBy('alert_type')->map->count(),
        ];

        return $summary;
    }

    /**
     * Acknowledge multiple alerts
     */
    public function acknowledgeAlerts(array $alertIds, ?string $username = null): int
    {
        return SystemAlert::whereIn('id', $alertIds)
            ->where('acknowledged', false)
            ->update([
                'acknowledged' => true,
                'acknowledged_at' => now(),
                'acknowledged_by' => $username ?? auth()->user()?->name ?? 'system',
            ]);
    }

    /**
     * Get alerts needing notification
     */
    public function getAlertsNeedingNotification(int $limit = 50): array
    {
        return SystemAlert::where('notified', false)
            ->where('severity', '!=', 'info')
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Clean up old acknowledged alerts
     */
    public function cleanupOldAlerts(int $daysToRetain = 30): int
    {
        return SystemAlert::where('acknowledged', true)
            ->where('acknowledged_at', '<=', now()->subDays($daysToRetain))
            ->delete();
    }
}
