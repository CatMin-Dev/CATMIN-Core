<?php

namespace Module\Webhooks\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookEvent extends Model
{
    protected $table = 'webhook_events';
    protected $fillable = ['webhook_id', 'event_id', 'event_type', 'payload', 'status', 'received_at'];
    protected $casts = [
        'payload' => 'array',
        'received_at' => 'datetime',
    ];

    public function webhook(): BelongsTo
    {
        return $this->belongsTo(Webhook::class);
    }
}
