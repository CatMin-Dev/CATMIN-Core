<?php

namespace Modules\Webhooks\Models;

use Illuminate\Database\Eloquent\Model;

class Webhook extends Model
{
    protected $table = 'webhooks';

    protected $fillable = [
        'name',
        'url',
        'events',
        'secret',
        'anti_replay_enabled',
        'rotation_status',
        'pending_secret',
        'pending_rotation_at',
        'status',
        'last_triggered_at',
        'last_delivery_status',
        'last_delivery_error',
        'last_delivery_at',
    ];

    protected function casts(): array
    {
        return [
            'events' => 'array',
            'anti_replay_enabled' => 'boolean',
            'pending_rotation_at' => 'datetime',
            'last_triggered_at' => 'datetime',
            'last_delivery_at' => 'datetime',
        ];
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
