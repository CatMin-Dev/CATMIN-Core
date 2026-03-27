<?php

namespace Modules\Webhooks\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Modules\Webhooks\Models\Webhook;

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
        $body = json_encode([
            'event' => $event,
            'timestamp' => now()->toIso8601String(),
            'data' => $payload,
        ], JSON_UNESCAPED_UNICODE);

        $headers = [
            'Content-Type' => 'application/json',
            'X-Catmin-Event' => $event,
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

            $webhook->update([
                'last_triggered_at' => now(),
                'last_delivery_at' => now(),
                'last_delivery_status' => $response->status(),
                'last_delivery_error' => null,
            ]);

            self::logOutgoing($webhook->id, $event, $body, $response->status(), true);
        } catch (\Throwable $e) {
            $webhook->update([
                'last_triggered_at' => now(),
                'last_delivery_at' => now(),
                'last_delivery_status' => 0,
                'last_delivery_error' => mb_substr($e->getMessage(), 0, 2000),
            ]);

            self::logOutgoing($webhook->id, $event, $body, 0, false, $e->getMessage());
        }
    }

    private static function logOutgoing(int $webhookId, string $event, string $body, int $code, bool $success, ?string $error = null): void
    {
        try {
            DB::table('system_logs')->insert([
                'channel' => 'webhooks',
                'level' => $success ? 'info' : 'error',
                'event' => 'webhook.outgoing',
                'message' => "webhook #{$webhookId} → {$event}",
                'context' => json_encode([
                    'webhook_id' => $webhookId,
                    'event' => $event,
                    'status_code' => $code,
                    'payload_size' => strlen($body),
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
