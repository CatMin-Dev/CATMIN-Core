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
            'last_triggered_at' => 'datetime',
            'last_delivery_at' => 'datetime',
        ];
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
