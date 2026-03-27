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
     * Validates the token against the catmin.webhooks.incoming_token setting
     * and logs the payload to system_logs (channel='webhooks').
     */
    public function receive(Request $request, string $token): JsonResponse
    {
        $expectedToken = config('catmin.webhooks.incoming_token')
            ?? env('CATMIN_WEBHOOK_INCOMING_TOKEN', '');

        if ($expectedToken === '' || !hash_equals($expectedToken, $token)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $payload = $request->all();
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
                'channel' => 'webhooks',
                'level' => 'info',
                'event' => 'webhook.incoming',
                'message' => 'Incoming webhook received',
                'context' => json_encode([
                    'payload' => $payload,
                    'signature' => $signature,
                ]),
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'ip_address' => $request->ip(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable) {
            // non-critical
        }
    }
}
