<?php

namespace Modules\Notifications\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AdminNotification extends Model
{
    protected $table = 'admin_notifications';

    protected $fillable = [
        'type',
        'source',
        'title',
        'message',
        'action_url',
        'action_label',
        'dedupe_key',
        'is_read',
        'is_acknowledged',
        'user_id',
        'context',
        'expires_at',
        'acknowledged_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'is_acknowledged' => 'boolean',
        'context' => 'array',
        'expires_at' => 'datetime',
        'acknowledged_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /** @return Builder<AdminNotification> */
    public static function scopeUnread(Builder $query): Builder
    {
        return $query->where('is_read', false);
    }

    /** @return Builder<AdminNotification> */
    public static function scopeUnacknowledged(Builder $query): Builder
    {
        return $query->where('is_acknowledged', false);
    }

    /** @return Builder<AdminNotification> */
    public static function scopeCritical(Builder $query): Builder
    {
        return $query->where('type', 'critical');
    }

    /** @return Builder<AdminNotification> */
    public static function scopeNotExpired(Builder $query): Builder
    {
        return $query->where(function (Builder $q): void {
            $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
        });
    }

    /** @return Builder<AdminNotification> */
    public static function scopeForAdmin(Builder $query, ?int $userId = null): Builder
    {
        return $query->where(function (Builder $q) use ($userId): void {
            $q->whereNull('user_id');
            if ($userId !== null) {
                $q->orWhere('user_id', $userId);
            }
        });
    }

    public function markRead(): void
    {
        $this->update(['is_read' => true]);
    }

    public function acknowledge(): void
    {
        $this->update([
            'is_acknowledged' => true,
            'is_read' => true,
            'acknowledged_at' => now(),
        ]);
    }

    public function isCritical(): bool
    {
        return $this->type === 'critical';
    }
}
