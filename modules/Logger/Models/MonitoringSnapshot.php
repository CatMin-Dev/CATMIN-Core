<?php

namespace Modules\Logger\Models;

use Illuminate\Database\Eloquent\Model;

class MonitoringSnapshot extends Model
{
    protected $table = 'monitoring_snapshots';

    protected $fillable = [
        'global_status',
        'score',
        'checks_json',
        'incidents_open',
        'incidents_critical',
    ];

    protected function casts(): array
    {
        return [
            'checks_json' => 'array',
            'score' => 'integer',
            'incidents_open' => 'integer',
            'incidents_critical' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
