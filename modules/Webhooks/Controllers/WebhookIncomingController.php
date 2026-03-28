<?php

namespace Modules\Webhooks\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Webhooks\Models\Webhook;
use Modules\Webhooks\Services\WebhookSecurityService;

class WebhookIncomingController extends Controller
{
    public function __construct(private readonly WebhookSecurityService $securityService)
    {
    }

    /**
     * Receive an incoming webhook payload.
     * 218 — Validates the token, logs invalid attempts, rejects missing/bad tokens.
     */
    public function receive(Request $request, string $token): JsonResponse
    {
        $expectedToken = config('catmin.webhooks.incoming_token')
            ?? env('CATMIN_WEBHOOK_INCOMING_TOKEN', '');

        if ($expectedToken === '' || !hash_equals($expectedToken, $token)) {
            // 218 — Log la tentative invalide avec IP, method, path
            $this->logInvalidAttempt($request);
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $webhook = $this->resolveIncomingWebhook($request);
        if (!$webhook instanceof Webhook) {
            $this->logInvalidAttempt($request, ['reason' => 'missing_or_invalid_webhook_id']);
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $rawBody = $request->getContent();
        $payload = $request->all();

        // Keep backward compatibility: fallback to global secret if webhook secret is not set.
        if (empty($webhook->secret) && !empty(config('catmin.webhooks.incoming_secret', ''))) {
            $webhook->secret = (string) config('catmin.webhooks.incoming_secret', '');
        }

        $signatureValidation = $this->securityService->validateSignature($request, $webhook, $rawBody);
        if (($signatureValidation['valid'] ?? false) !== true) {
            $receivedSignature = (string) ($request->header('X-Catmin-Signature') ?? $request->header('X-Hub-Signature-256') ?? '');
            $this->logInvalidSignature($request, $receivedSignature, [
                'reason' => (string) ($signatureValidation['error'] ?? 'signature_validation_failed'),
                'webhook_id' => $webhook->id,
            ]);
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $securityValidation = $this->securityService->validateIncomingWebhook($request, $webhook);
        if (($securityValidation['valid'] ?? false) !== true) {
            $duplicate = (bool) ($securityValidation['duplicate'] ?? false);
            if ($duplicate) {
                $this->logIncoming($request, $payload, (string) ($request->header('X-Catmin-Signature') ?? ''), [
                    'webhook_id' => $webhook->id,
                    'duplicate' => true,
                    'errors' => $securityValidation['errors'] ?? [],
                ]);
                return response()->json(['status' => 'duplicate_ignored'], 202);
            }

            $this->logInvalidAttempt($request, [
                'reason' => 'anti_replay_or_idempotence_failed',
                'webhook_id' => $webhook->id,
                'errors' => $securityValidation['errors'] ?? [],
            ]);
            return response()->json(['error' => 'Rejected'], 401);
        }

        $signature = $request->header('X-Hub-Signature-256')
            ?? $request->header('X-Signature')
            ?? null;

        $eventId = (string) ($request->header('X-Catmin-Event-Id') ?? '');
        if ($eventId !== '') {
            $eventType = (string) ($request->header('X-Catmin-Event') ?? ($payload['event'] ?? 'incoming.webhook'));
            $this->securityService->recordWebhookEvent($webhook, $eventId, $eventType, $payload, 'processed');
        }

        $this->logIncoming($request, $payload, $signature, ['webhook_id' => $webhook->id]);

        return response()->json(['status' => 'received'], 200);
    }

    private function resolveIncomingWebhook(Request $request): ?Webhook
    {
        $headerId = $request->header('X-Catmin-Webhook-Id');
        $configId = config('catmin.webhooks.incoming_webhook_id');
        $webhookId = is_numeric($headerId) ? (int) $headerId : (is_numeric((string) $configId) ? (int) $configId : 0);

        if ($webhookId <= 0) {
            return null;
        }

        return Webhook::query()
            ->where('id', $webhookId)
            ->where('status', 'active')
            ->first();
    }

    /**
     * @param array<string, mixed> $extraContext
     */
    private function logIncoming(Request $request, array $payload, ?string $signature, array $extraContext = []): void
    {
        try {
            DB::table('system_logs')->insert([
                'channel'    => 'webhooks',
                'level'      => 'info',
                'event'      => 'webhook.incoming',
                'message'    => 'Incoming webhook received',
                'context'    => json_encode(array_merge([
                    'payload' => $payload,
                    'signature' => $signature,
                    'timestamp' => $request->header('X-Catmin-Timestamp'),
                    'nonce' => $request->header('X-Catmin-Nonce'),
                    'event_id' => $request->header('X-Catmin-Event-Id'),
                ], $extraContext)),
                'method'     => $request->method(),
                'url'        => $request->fullUrl(),
                'ip_address' => $request->ip(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable) {}
    }

    /**
     * @param array<string, mixed> $extraContext
     */
    private function logInvalidAttempt(Request $request, array $extraContext = []): void
    {
        try {
            DB::table('system_logs')->insert([
                'channel'    => 'webhooks',
                'level'      => 'warning',
                'event'      => 'webhook.incoming.unauthorized',
                'message'    => 'Incoming webhook rejected — token invalide ou manquant',
                'context'    => json_encode(array_merge([
                    'ip' => $request->ip(),
                    'path' => $request->path(),
                    'timestamp' => $request->header('X-Catmin-Timestamp'),
                    'nonce' => $request->header('X-Catmin-Nonce'),
                    'event_id' => $request->header('X-Catmin-Event-Id'),
                ], $extraContext)),
                'method'     => $request->method(),
                'url'        => $request->fullUrl(),
                'ip_address' => $request->ip(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable) {}
    }

    /**
     * @param array<string, mixed> $extraContext
     */
    private function logInvalidSignature(Request $request, string $receivedSig, array $extraContext = []): void
    {
        try {
            DB::table('system_logs')->insert([
                'channel'    => 'webhooks',
                'level'      => 'warning',
                'event'      => 'webhook.incoming.bad_signature',
                'message'    => 'Incoming webhook rejected — signature HMAC invalide',
                'context'    => json_encode(array_merge([
                    'ip' => $request->ip(),
                    'received_sig' => substr($receivedSig, 0, 20) . '...',
                ], $extraContext)),
                'method'     => $request->method(),
                'url'        => $request->fullUrl(),
                'ip_address' => $request->ip(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable) {}
    }
}

