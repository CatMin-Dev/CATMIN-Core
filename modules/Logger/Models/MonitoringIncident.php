<?php

namespace Modules\Logger\Models;

use Illuminate\Database\Eloquent\Model;

class MonitoringIncident extends Model
{
    protected $table = 'monitoring_incidents';

    protected $fillable = [
        'fingerprint',
        'domain',
        'severity',
        'status',
        'title',
        'message',
        'occurrences',
        'first_seen_at',
        'last_seen_at',
        'recovered_at',
        'context',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
            'occurrences' => 'integer',
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'recovered_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
