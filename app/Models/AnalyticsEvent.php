<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnalyticsEvent extends Model
{
    protected $fillable = [
        'event_name',
        'domain',
        'action',
        'status',
        'actor_type',
        'actor_id',
        'role',
        'route_name',
        'path',
        'context',
        'metadata',
        'occurred_at',
    ];

    protected $casts = [
        'context' => 'array',
        'metadata' => 'array',
        'occurred_at' => 'datetime',
    ];
}
