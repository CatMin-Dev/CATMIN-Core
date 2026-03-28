<?php

namespace Modules\Webhooks\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookDelivery extends Model
{
    protected $table = 'webhook_deliveries';
    protected $fillable = [
        'webhook_id',
        'event_type',
        'payload',
        'status',
        'attempt_number',
        'max_attempts',
        'next_retry_at',
        'response_code',
        'response_body',
        'error_message',
        'sent_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'next_retry_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function webhook(): BelongsTo
    {
        return $this->belongsTo(Webhook::class);
    }

    /**
     * Get delivery attempts that need retrying
     */
    public static function scopePendingRetry($query)
    {
        return $query->where('status', 'retrying')
            ->where('next_retry_at', '<=', now())
            ->where('attempt_number', '<', $query->getModel()->max_attempts);
    }

    /**
     * Mark delivery as successful
     */
    public function markSuccessful(string $responseCode, ?string $responseBody = null): void
    {
        $this->update([
            'status' => 'success',
            'response_code' => $responseCode,
            'response_body' => $responseBody,
            'sent_at' => now(),
        ]);
    }

    /**
     * Mark delivery as failed with retry
     */
    public function markFailedWithRetry(string $errorMessage, ?string $responseCode = null): void
    {
        $nextAttempt = $this->attempt_number + 1;
        $retryDelay = min(pow(2, $this->attempt_number) * 60, 3600); // Exponential backoff, max 1 hour

        $this->update([
            'status' => $nextAttempt >= $this->max_attempts ? 'failed' : 'retrying',
            'attempt_number' => $nextAttempt,
            'error_message' => $errorMessage,
            'response_code' => $responseCode,
            'next_retry_at' => now()->addSeconds($retryDelay),
        ]);
    }

    /**
     * Mark delivery as permanently failed
     */
    public function markFailed(string $errorMessage, ?string $responseCode = null): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'response_code' => $responseCode,
        ]);
    }
}
