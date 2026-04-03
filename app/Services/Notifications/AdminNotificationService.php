<?php

namespace App\Services\Notifications;

use App\Services\SettingService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Notifications\Models\AdminNotification;

class AdminNotificationService
{
    private const string VALID_TYPES = 'info|warning|critical|success';
    private const int DEFAULT_TTL_DAYS = 30;
    private const int EMAIL_CRITICAL_THRESHOLD = 3;

    /**
     * Create a notification, skipping if dedupe_key already exists in the last 24h.
     * Returns the existing or newly created notification.
     */
    public static function notify(
        string $title,
        string $message,
        string $type = 'info',
        string $source = 'system',
        ?string $actionUrl = null,
        ?string $actionLabel = null,
        array $context = [],
        ?string $dedupeKey = null,
        ?int $ttlMinutes = null,
    ): AdminNotification {
        $type = in_array($type, ['info', 'warning', 'critical', 'success'], true) ? $type : 'info';
        $dedupeKey = $dedupeKey !== null && $dedupeKey !== '' ? $dedupeKey : null;

        if ($dedupeKey !== null) {
            $existing = AdminNotification::query()
                ->where('dedupe_key', $dedupeKey)
                ->where('created_at', '>=', now()->subHours(24))
                ->first();

            if ($existing !== null) {
                return $existing;
            }
        }

        $expiresAt = $ttlMinutes !== null
            ? now()->addMinutes($ttlMinutes)
            : now()->addDays(self::DEFAULT_TTL_DAYS);

        $notification = AdminNotification::query()->create([
            'type' => $type,
            'source' => $source,
            'title' => $title,
            'message' => $message,
            'action_url' => $actionUrl,
            'action_label' => $actionLabel,
            'dedupe_key' => $dedupeKey,
            'context' => $context,
            'expires_at' => $expiresAt,
            'is_read' => false,
            'is_acknowledged' => false,
        ]);

        if ($type === 'critical') {
            static::maybeSendCriticalEmail($notification);
        }

        return $notification;
    }

    /**
     * Shorthand for a critical notification.
     */
    public static function critical(
        string $title,
        string $message,
        string $source = 'system',
        ?string $actionUrl = null,
        ?string $actionLabel = null,
        array $context = [],
        ?string $dedupeKey = null,
    ): AdminNotification {
        return static::notify($title, $message, 'critical', $source, $actionUrl, $actionLabel, $context, $dedupeKey);
    }

    /**
     * Shorthand for a warning notification.
     */
    public static function warning(
        string $title,
        string $message,
        string $source = 'system',
        ?string $actionUrl = null,
        ?string $actionLabel = null,
        array $context = [],
        ?string $dedupeKey = null,
    ): AdminNotification {
        return static::notify($title, $message, 'warning', $source, $actionUrl, $actionLabel, $context, $dedupeKey);
    }

    /**
     * Count unread notifications for topbar badge.
     */
    public static function unreadCount(?int $userId = null): int
    {
        return (int) AdminNotification::query()
            ->notExpired()
            ->unread()
            ->forAdmin($userId)
            ->count();
    }

    /**
     * Count unread critical notifications.
     */
    public static function unreadCriticalCount(?int $userId = null): int
    {
        return (int) AdminNotification::query()
            ->notExpired()
            ->unread()
            ->critical()
            ->forAdmin($userId)
            ->count();
    }

    /**
     * Latest unread notifications for the topbar dropdown.
     *
     * @return array<int, AdminNotification>
     */
    public static function latestForDropdown(int $limit = 8, ?int $userId = null): array
    {
        return AdminNotification::query()
            ->notExpired()
            ->forAdmin($userId)
            ->orderByDesc('created_at')
            ->limit(max(1, $limit))
            ->get()
            ->all();
    }

    /**
     * Paginated listing with filters.
     *
     * @param array<string, mixed> $filters
     */
    public static function listing(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        $query = AdminNotification::query()
            ->notExpired()
            ->orderByDesc('created_at');

        $type = trim((string) ($filters['type'] ?? ''));
        $source = trim((string) ($filters['source'] ?? ''));
        $readState = trim((string) ($filters['read'] ?? ''));
        $criticalOnly = filter_var($filters['critical_only'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if ($type !== '') {
            $query->where('type', $type);
        }

        if ($source !== '') {
            $query->where('source', $source);
        }

        if ($readState === 'unread') {
            $query->where('is_read', false);
        } elseif ($readState === 'read') {
            $query->where('is_read', true);
        }

        if ($criticalOnly) {
            $query->where('type', 'critical');
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Mark a single notification as read.
     */
    public static function markRead(AdminNotification $notification): void
    {
        $notification->markRead();
    }

    /**
     * Mark all unread as read.
     */
    public static function markAllRead(?int $userId = null): int
    {
        return AdminNotification::query()
            ->notExpired()
            ->unread()
            ->forAdmin($userId)
            ->update(['is_read' => true]);
    }

    /**
     * Acknowledge a notification.
     */
    public static function acknowledge(AdminNotification $notification): void
    {
        $notification->acknowledge();
    }

    /**
     * Bulk mark-as-read by ids.
     *
     * @param array<int, int> $ids
     */
    public static function bulkRead(array $ids): int
    {
        if ($ids === []) {
            return 0;
        }

        return AdminNotification::query()
            ->whereIn('id', $ids)
            ->update(['is_read' => true]);
    }

    /**
     * Bulk acknowledge by ids.
     *
     * @param array<int, int> $ids
     */
    public static function bulkAcknowledge(array $ids): int
    {
        if ($ids === []) {
            return 0;
        }

        return AdminNotification::query()
            ->whereIn('id', $ids)
            ->update([
                'is_acknowledged' => true,
                'is_read' => true,
                'acknowledged_at' => now(),
            ]);
    }

    /**
     * Purge expired notifications.
     */
    public static function purgeExpired(): int
    {
        return AdminNotification::query()
            ->where('expires_at', '<', now())
            ->delete();
    }

    /**
     * Send email to admin if critical threshold is exceeded in the last hour.
     */
    private static function maybeSendCriticalEmail(AdminNotification $notification): void
    {
        try {
            $emailTo = (string) SettingService::get('ops.alert_email', '');
            if ($emailTo === '') {
                return;
            }

            $criticalRecent = (int) AdminNotification::query()
                ->where('type', 'critical')
                ->where('created_at', '>=', now()->subHour())
                ->count();

            if ($criticalRecent < self::EMAIL_CRITICAL_THRESHOLD) {
                return;
            }

            Mail::raw(
                "[CATMIN] Alerte critique #{$notification->id}: {$notification->title}\n\n{$notification->message}",
                static function ($mail) use ($emailTo, $notification): void {
                    $mail->to($emailTo)
                        ->subject('[CATMIN CRITICAL] ' . $notification->title);
                }
            );
        } catch (\Throwable $e) {
            Log::warning('AdminNotificationService: email critique non envoyé', [
                'notification_id' => $notification->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Summary stats for dashboard widget.
     *
     * @return array<string, int>
     */
    public static function dashboardStats(?int $userId = null): array
    {
        $base = AdminNotification::query()->notExpired()->forAdmin($userId);

        return [
            'unread' => (int) (clone $base)->where('is_read', false)->count(),
            'critical' => (int) (clone $base)->where('type', 'critical')->where('is_read', false)->count(),
            'warning' => (int) (clone $base)->where('type', 'warning')->where('is_read', false)->count(),
            'unacknowledged' => (int) (clone $base)->where('is_acknowledged', false)->where('type', 'critical')->count(),
        ];
    }
}
