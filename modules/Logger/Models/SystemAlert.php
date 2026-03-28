<?php

namespace Module\Logger\Models;

use Illuminate\Database\Eloquent\Model;

class SystemAlert extends Model
{
    protected $table = 'system_alerts';
    
    protected $fillable = [
        'alert_type',
        'severity',
        'title',
        'message',
        'context',
        'acknowledged',
        'acknowledged_at',
        'acknowledged_by',
        'notified',
        'notified_at',
        'notification_channels',
    ];

    protected $casts = [
        'context' => 'array',
        'acknowledged' => 'boolean',
        'notified' => 'boolean',
        'acknowledged_at' => 'datetime',
        'notified_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get unacknowledged alerts
     */
    public static function scopeUnacknowledged($query)
    {
        return $query->where('acknowledged', false);
    }

    /**
     * Get critical alerts
     */
    public static function scopeCritical($query)
    {
        return $query->where('severity', 'critical');
    }

    /**
     * Get alerts by type
     */
    public static function scopeByType($query, string $type)
    {
        return $query->where('alert_type', $type);
    }

    /**
     * Get recent unacknowledged alerts
     */
    public static function scopeRecentUnacknowledged($query, int $hours = 24)
    {
        return $query->unacknowledged()
            ->where('created_at', '>=', now()->subHours($hours))
            ->orderBy('created_at', 'desc');
    }

    /**
     * Acknowledge the alert
     */
    public function acknowledge(?string $username = null): void
    {
        $this->update([
            'acknowledged' => true,
            'acknowledged_at' => now(),
            'acknowledged_by' => $username ?? auth()->user()?->name ?? 'system',
        ]);
    }

    /**
     * Mark as notified
     */
    public function markNotified(array $channels = []): void
    {
        $this->update([
            'notified' => true,
            'notified_at' => now(),
            'notification_channels' => implode(',', $channels),
        ]);
    }
}
