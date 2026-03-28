<?php

namespace Modules\Webhooks\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Modules\Webhooks\Models\Webhook;
use Modules\Webhooks\Models\WebhookNonce;
use Modules\Webhooks\Models\WebhookEvent;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class WebhookSecurityService
{
    /**
     * Configuration for anti-replay protection
     */
    private const TIMESTAMP_TTL_SECONDS = 300; // 5 minutes
    private const NONCE_TTL_SECONDS = 3600; // 1 hour

    /**
     * Validate incoming webhook with full security checks
     */
    public function validateIncomingWebhook(Request $request, Webhook $webhook): array
    {
        $errors = [];
        $isDuplicate = false;

        // 1. Verify anti-replay protection is enabled for this webhook
        if ($webhook->anti_replay_enabled) {
            // Check timestamp header
            $timestamp = $request->header('X-Catmin-Timestamp');
            if (!$timestamp) {
                $errors[] = 'Missing X-Catmin-Timestamp header';
            } else {
                $timestampValidation = $this->validateTimestamp($timestamp);
                if (!$timestampValidation['valid']) {
                    $errors[] = $timestampValidation['error'];
                }
            }

            // Check nonce header
            $nonce = $request->header('X-Catmin-Nonce');
            if (!$nonce) {
                $errors[] = 'Missing X-Catmin-Nonce header';
            } else {
                $nonceValidation = $this->validateNonce($webhook, $nonce);
                if (!$nonceValidation['valid']) {
                    $errors[] = $nonceValidation['error'];
                }
            }
        }

        // 2. Check event ID for idempotence
        $eventId = $request->header('X-Catmin-Event-Id');
        if ($eventId) {
            $eventValidation = $this->validateEventId($webhook, $eventId);
            if (!$eventValidation['valid']) {
                $errors[] = $eventValidation['error'];
                $isDuplicate = (bool) ($eventValidation['duplicate'] ?? false);
            }
        }

        return [
            'valid' => count($errors) === 0,
            'duplicate' => $isDuplicate,
            'errors' => $errors,
        ];
    }

    /**
     * Validate timestamp is within acceptable window
     */
    private function validateTimestamp(string $timestamp): array
    {
        try {
            $requestTime = Carbon::parse($timestamp);
            $now = Carbon::now();
            $diff = abs($now->diffInSeconds($requestTime));

            if ($diff > self::TIMESTAMP_TTL_SECONDS) {
                return [
                    'valid' => false,
                    'error' => 'Timestamp expired (TTL ' . self::TIMESTAMP_TTL_SECONDS . 's)',
                ];
            }

            return ['valid' => true];
        } catch (\Exception $e) {
            return [
                'valid' => false,
                'error' => 'Invalid timestamp format',
            ];
        }
    }

    /**
     * Validate nonce has not been used before
     */
    private function validateNonce(Webhook $webhook, string $nonce): array
    {
        // Check if nonce already exists
        $existingNonce = WebhookNonce::where('webhook_id', $webhook->id)
            ->where('nonce', $nonce)
            ->first();

        if ($existingNonce) {
            return [
                'valid' => false,
                'error' => 'Nonce already used (replay protection)',
            ];
        }

        // Store the nonce
        try {
            WebhookNonce::create([
                'webhook_id' => $webhook->id,
                'nonce' => $nonce,
                'expires_at' => Carbon::now()->addSeconds(self::NONCE_TTL_SECONDS),
            ]);

            return ['valid' => true];
        } catch (\Exception $e) {
            Log::warning('Failed to store webhook nonce', [
                'webhook_id' => $webhook->id,
                'nonce' => substr($nonce, 0, 10) . '...',
                'error' => $e->getMessage(),
            ]);

            return [
                'valid' => false,
                'error' => 'Failed to validate nonce',
            ];
        }
    }

    /**
     * Validate event ID for idempotence
     */
    private function validateEventId(Webhook $webhook, string $eventId): array
    {
        $existingEvent = WebhookEvent::where('webhook_id', $webhook->id)
            ->where('event_id', $eventId)
            ->first();

        if ($existingEvent) {
            if ($existingEvent->status === 'processed') {
                return [
                    'valid' => false,
                    'error' => 'Event already processed',
                    'duplicate' => true,
                ];
            } elseif ($existingEvent->status === 'failed') {
                return [
                    'valid' => false,
                    'error' => 'Event previously failed',
                    'duplicate' => false,
                ];
            }
        }

        return ['valid' => true];
    }

    /**
     * Record incoming webhook event for tracking
     */
    public function recordWebhookEvent(
        Webhook $webhook,
        string $eventId,
        string $eventType,
        array $payload = [],
        string $status = 'processed'
    ): WebhookEvent {
        return WebhookEvent::create([
            'webhook_id' => $webhook->id,
            'event_id' => $eventId,
            'event_type' => $eventType,
            'payload' => $payload,
            'status' => $status,
            'received_at' => now(),
        ]);
    }

    /**
     * Validate webhook signature (existing HMAC validation)
     */
    public function validateSignature(Request $request, Webhook $webhook, string $payload): array
    {
        $signature = $request->header('X-Catmin-Signature') 
            ?? $request->header('X-Hub-Signature-256');

        if (!$signature) {
            return [
                'valid' => true, // Signature is optional
                'signed' => false,
            ];
        }

        if (empty($webhook->secret)) {
            return [
                'valid' => false,
                'signed' => true,
                'error' => 'No webhook secret configured for signature validation',
            ];
        }

        // Use current secret or pending secret if rotation is in progress.
        if ($webhook->rotation_status === 'pending' && !empty($webhook->pending_secret)) {
            // Accept both current and pending during rotation
            $expectedCurrent = 'sha256=' . hash_hmac('sha256', $payload, $webhook->secret);
            $pendingPlain = $this->decryptSecret($webhook->pending_secret);
            $expectedPending = 'sha256=' . hash_hmac('sha256', $payload, $pendingPlain);

            if (hash_equals($signature, $expectedCurrent) || hash_equals($signature, $expectedPending)) {
                return ['valid' => true, 'signed' => true];
            }
        } else {
            $expected = 'sha256=' . hash_hmac('sha256', $payload, $webhook->secret);
            if (hash_equals($signature, $expected)) {
                return ['valid' => true, 'signed' => true];
            }
        }

        return [
            'valid' => false,
            'signed' => true,
            'error' => 'Signature validation failed',
        ];
    }

    /**
     * Initiate secret rotation for a webhook
     */
    public function initiateSecretRotation(Webhook $webhook, ?string $newSecret = null): void
    {
        $newSecret = $newSecret ?? bin2hex(random_bytes(32));

        $webhook->update([
            'rotation_status' => 'pending',
            'pending_secret' => Crypt::encrypt($newSecret),
            'pending_rotation_at' => now()->addHours(24),
        ]);

        Log::info('Webhook secret rotation initiated', [
            'webhook_id' => $webhook->id,
            'rotation_at' => $webhook->pending_rotation_at,
        ]);
    }

    /**
     * Complete secret rotation
     */
    public function completeSecretRotation(Webhook $webhook): void
    {
        if ($webhook->rotation_status !== 'pending') {
            return;
        }

        $webhook->update([
            'secret' => $this->decryptSecret($webhook->pending_secret),
            'rotation_status' => 'current',
            'pending_secret' => null,
            'pending_rotation_at' => null,
        ]);

        Log::info('Webhook secret rotation completed', [
            'webhook_id' => $webhook->id,
        ]);
    }

    /**
     * Decrypt an encrypted secret, returning the input as-is if decryption fails
     * (backward compat for secrets stored before encryption was added).
     */
    private function decryptSecret(?string $encrypted): string
    {
        if ($encrypted === null || $encrypted === '') {
            return '';
        }

        try {
            return (string) Crypt::decrypt($encrypted);
        } catch (\Throwable) {
            // Legacy plain-text secret — return as-is
            return $encrypted;
        }
    }

    /**
     * Clean up expired nonces
     */
    public function cleanupExpiredNonces(): int
    {
        return WebhookNonce::where('expires_at', '<=', now())->delete();
    }

    /**
     * Clean up old webhook events based on retention
     */
    public function cleanupOldEvents(int $daysToRetain = 30): int
    {
        return WebhookEvent::where('created_at', '<=', now()->subDays($daysToRetain))->delete();
    }
}
