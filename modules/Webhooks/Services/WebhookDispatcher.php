<?php

namespace Modules\Webhooks\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Modules\Webhooks\Models\Webhook;
use Modules\Webhooks\Models\WebhookDelivery;
use Modules\Logger\Services\AlertingService;

class WebhookDispatcher
{
    /**
     * Fire all active webhooks subscribed to the given event.
     *
     * @param array<string, mixed> $payload
     */
    public static function dispatch(string $event, array $payload = []): void
    {
        $webhooks = Webhook::query()
            ->where('status', 'active')
            ->get();

        foreach ($webhooks as $webhook) {
            $events = $webhook->events ?? [];
            if (!in_array($event, $events, true) && !in_array('*', $events, true)) {
                continue;
            }

            self::send($webhook, $event, $payload);
        }
    }

    /**
     * Send a single webhook request.
     */
    public static function send(Webhook $webhook, string $event, array $payload): void
    {
        $eventId = (string) ($payload['event_id'] ?? uniqid('evt_', true));
        $timestamp = now()->toIso8601String();
        $nonce = bin2hex(random_bytes(16));

        $body = json_encode([
            'event' => $event,
            'event_id' => $eventId,
            'timestamp' => $timestamp,
            'data' => $payload,
        ], JSON_UNESCAPED_UNICODE);

        if ($body === false) {
            return;
        }

        $delivery = WebhookDelivery::query()->create([
            'webhook_id' => $webhook->id,
            'event_type' => $event,
            'payload' => $payload,
            'status' => 'sending',
            'attempt_number' => 1,
            'max_attempts' => 5,
        ]);

        $headers = [
            'Content-Type' => 'application/json',
            'X-Catmin-Event' => $event,
            'X-Catmin-Event-Id' => $eventId,
            'X-Catmin-Timestamp' => $timestamp,
            'X-Catmin-Nonce' => $nonce,
            'X-Catmin-Webhook-Id' => (string) $webhook->id,
        ];

        if (!empty($webhook->secret)) {
            $signature = hash_hmac('sha256', $body, $webhook->secret);
            $headers['X-Catmin-Signature'] = 'sha256=' . $signature;
        }

        try {
            $response = Http::withHeaders($headers)
                ->timeout(10)
                ->withBody($body, 'application/json')
                ->post($webhook->url);

            $statusCode = (int) $response->status();
            $responseBody = mb_substr((string) $response->body(), 0, 4000);
            $isSuccess = $statusCode >= 200 && $statusCode < 300;

            $webhook->update([
                'last_triggered_at' => now(),
                'last_delivery_at' => now(),
                'last_delivery_status' => $statusCode,
                'last_delivery_error' => $isSuccess ? null : ('HTTP ' . $statusCode),
            ]);

            if ($isSuccess) {
                $delivery->markSuccessful((string) $statusCode, $responseBody);
            } else {
                $delivery->markFailedWithRetry('HTTP ' . $statusCode, (string) $statusCode);
                app(AlertingService::class)->alertWebhookRetrying(
                    $webhook->id,
                    $webhook->url,
                    (int) $delivery->attempt_number,
                    (int) $delivery->max_attempts
                );
            }

            self::logOutgoing($webhook->id, $event, $body, $statusCode, $isSuccess, $isSuccess ? null : ('HTTP ' . $statusCode), (int) $delivery->id, (int) $delivery->attempt_number);
        } catch (\Throwable $e) {
            $webhook->update([
                'last_triggered_at' => now(),
                'last_delivery_at' => now(),
                'last_delivery_status' => 0,
                'last_delivery_error' => mb_substr($e->getMessage(), 0, 2000),
            ]);

            $delivery->markFailedWithRetry($e->getMessage());
            app(AlertingService::class)->alertWebhookFailed($webhook->id, $webhook->url, $e->getMessage(), null);
            self::logOutgoing($webhook->id, $event, $body, 0, false, $e->getMessage(), (int) $delivery->id, (int) $delivery->attempt_number);
        }
    }

    private static function logOutgoing(int $webhookId, string $event, string $body, int $code, bool $success, ?string $error = null, ?int $deliveryId = null, int $attempt = 1): void
    {
        try {
            DB::table('system_logs')->insert([
                'channel' => 'webhooks',
                'level' => $success ? 'info' : 'error',
                'event' => 'webhook.outgoing',
                'message' => "webhook #{$webhookId} → {$event}",
                'context' => json_encode([
                    'webhook_id' => $webhookId,
                    'delivery_id' => $deliveryId,
                    'event' => $event,
                    'attempt' => $attempt,
                    'status_code' => $code,
                    'payload_size' => strlen($body),
                    'payload_hash' => hash('sha256', $body),
                    'error' => $error,
                ]),
                'status_code' => $code,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable) {
            // non-critical
        }
    }
}
