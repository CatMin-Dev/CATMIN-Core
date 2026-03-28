<?php

namespace Modules\Webhooks\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookNonce extends Model
{
    protected $table = 'webhook_nonces';
    protected $fillable = ['webhook_id', 'nonce', 'expires_at'];
    protected $casts = ['expires_at' => 'datetime'];

    public function webhook(): BelongsTo
    {
        return $this->belongsTo(Webhook::class);
    }
}
