<?php

namespace Modules\Webhooks\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WebhookIncomingController extends Controller
{
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

        $rawBody = $request->getContent();
        $payload = $request->all();

        // 218 — Vérification signature HMAC si un secret webhook est configuré
        $webhookSecret = config('catmin.webhooks.incoming_secret', '');
        if (!empty($webhookSecret)) {
            $signature = $request->header('X-Hub-Signature-256')
                ?? $request->header('X-Catmin-Signature')
                ?? '';

            $expectedSig = 'sha256=' . hash_hmac('sha256', $rawBody, $webhookSecret);
            if (!hash_equals($expectedSig, (string) $signature)) {
                $this->logInvalidSignature($request, (string) $signature);
                return response()->json(['error' => 'Invalid signature'], 401);
            }
        }

        $signature = $request->header('X-Hub-Signature-256')
            ?? $request->header('X-Signature')
            ?? null;

        $this->logIncoming($request, $payload, $signature);

        return response()->json(['status' => 'received'], 200);
    }

    private function logIncoming(Request $request, array $payload, ?string $signature): void
    {
        try {
            DB::table('system_logs')->insert([
                'channel'    => 'webhooks',
                'level'      => 'info',
                'event'      => 'webhook.incoming',
                'message'    => 'Incoming webhook received',
                'context'    => json_encode(['payload' => $payload, 'signature' => $signature]),
                'method'     => $request->method(),
                'url'        => $request->fullUrl(),
                'ip_address' => $request->ip(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable) {}
    }

    private function logInvalidAttempt(Request $request): void
    {
        try {
            DB::table('system_logs')->insert([
                'channel'    => 'webhooks',
                'level'      => 'warning',
                'event'      => 'webhook.incoming.unauthorized',
                'message'    => 'Incoming webhook rejected — token invalide ou manquant',
                'context'    => json_encode(['ip' => $request->ip(), 'path' => $request->path()]),
                'method'     => $request->method(),
                'url'        => $request->fullUrl(),
                'ip_address' => $request->ip(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable) {}
    }

    private function logInvalidSignature(Request $request, string $receivedSig): void
    {
        try {
            DB::table('system_logs')->insert([
                'channel'    => 'webhooks',
                'level'      => 'warning',
                'event'      => 'webhook.incoming.bad_signature',
                'message'    => 'Incoming webhook rejected — signature HMAC invalide',
                'context'    => json_encode(['ip' => $request->ip(), 'received_sig' => substr($receivedSig, 0, 20) . '...']),
                'method'     => $request->method(),
                'url'        => $request->fullUrl(),
                'ip_address' => $request->ip(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable) {}
    }
}

