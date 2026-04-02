<?php

namespace Tests\Unit\Webhooks;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;
use Modules\Webhooks\Models\Webhook;
use Modules\Webhooks\Models\WebhookDelivery;
use Modules\Webhooks\Services\WebhookSecurityService;
use Tests\TestCase;

class WebhookSecurityServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('pdo_sqlite extension is not available.');
        }

        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', ':memory:');

        app('db')->purge('sqlite');
        app('db')->reconnect('sqlite');

        $this->createTables();
    }

    public function test_rejects_expired_timestamp(): void
    {
        $service = app(WebhookSecurityService::class);
        $webhook = Webhook::query()->create([
            'name' => 'Incoming',
            'url' => 'https://example.test/webhook',
            'events' => ['*'],
            'secret' => 'secret',
            'status' => 'active',
            'anti_replay_enabled' => true,
            'rotation_status' => 'current',
        ]);

        $request = Request::create('/webhooks/incoming/token', 'POST', [], [], [], [], json_encode(['x' => 1]));
        $request->headers->set('X-Catmin-Timestamp', now()->subMinutes(30)->toIso8601String());
        $request->headers->set('X-Catmin-Nonce', 'nonce-1');

        $result = $service->validateIncomingWebhook($request, $webhook);

        $this->assertFalse((bool) $result['valid']);
    }

    public function test_rejects_nonce_replay(): void
    {
        $service = app(WebhookSecurityService::class);
        $webhook = Webhook::query()->create([
            'name' => 'Incoming',
            'url' => 'https://example.test/webhook',
            'events' => ['*'],
            'secret' => 'secret',
            'status' => 'active',
            'anti_replay_enabled' => true,
            'rotation_status' => 'current',
        ]);

        $payload = json_encode(['x' => 1]);

        $requestA = Request::create('/webhooks/incoming/token', 'POST', [], [], [], [], $payload);
        $requestA->headers->set('X-Catmin-Timestamp', now()->toIso8601String());
        $requestA->headers->set('X-Catmin-Nonce', 'nonce-replay');

        $first = $service->validateIncomingWebhook($requestA, $webhook);
        $this->assertTrue((bool) $first['valid']);

        $requestB = Request::create('/webhooks/incoming/token', 'POST', [], [], [], [], $payload);
        $requestB->headers->set('X-Catmin-Timestamp', now()->toIso8601String());
        $requestB->headers->set('X-Catmin-Nonce', 'nonce-replay');

        $second = $service->validateIncomingWebhook($requestB, $webhook);
        $this->assertFalse((bool) $second['valid']);
    }

    private function createTables(): void
    {
        Schema::dropAllTables();

        Schema::create('webhooks', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('url', 500);
            $table->json('events')->nullable();
            $table->string('secret')->nullable();
            $table->boolean('anti_replay_enabled')->default(true);
            $table->string('rotation_status')->default('current');
            $table->string('pending_secret')->nullable();
            $table->timestamp('pending_rotation_at')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamp('last_triggered_at')->nullable();
            $table->unsignedSmallInteger('last_delivery_status')->nullable();
            $table->text('last_delivery_error')->nullable();
            $table->timestamp('last_delivery_at')->nullable();
            $table->timestamps();
        });

        Schema::create('webhook_nonces', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('webhook_id');
            $table->string('nonce', 255)->unique();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('webhook_events', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('webhook_id');
            $table->string('event_id', 255)->unique();
            $table->string('event_type', 255)->nullable();
            $table->timestamp('received_at')->nullable();
            $table->json('payload')->nullable();
            $table->string('status')->default('processed');
            $table->timestamps();
        });

        Schema::create('webhook_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('webhook_id');
            $table->string('event_type', 255)->nullable();
            $table->json('payload')->nullable();
            $table->string('status')->default('pending');
            $table->integer('attempt_number')->default(1);
            $table->integer('max_attempts')->default(5);
            $table->timestamp('next_retry_at')->nullable();
            $table->string('response_code')->nullable();
            $table->text('response_body')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('dead_letter_at')->nullable();
            $table->text('dlq_reason')->nullable();
            $table->timestamps();
        });
    }

    // ─── Test: event_id idempotence (doublon ignoré) ──────────────────────────

    public function test_event_id_idempotency_rejects_duplicate(): void
    {
        $service = app(WebhookSecurityService::class);
        $webhook = Webhook::query()->create([
            'name' => 'Idempotence',
            'url' => 'https://example.test/webhook',
            'events' => ['*'],
            'secret' => null,
            'status' => 'active',
            'anti_replay_enabled' => false, // disable nonce/timestamp checks, focus on event_id
            'rotation_status' => 'current',
        ]);

        $eventId = 'evt_' . uniqid();

        // First call: records the event
        $service->recordWebhookEvent($webhook, $eventId, 'user.created', [], 'processed');

        // Second call with same event_id: should be detected as duplicate
        $request = Request::create('/webhooks/incoming/token', 'POST', [], [], [], [], json_encode(['x' => 1]));
        $request->headers->set('X-Catmin-Event-Id', $eventId);
        // No nonce/timestamp needed since anti_replay_enabled = false

        $result = $service->validateIncomingWebhook($request, $webhook);

        $this->assertFalse((bool) $result['valid']);
        $this->assertTrue((bool) $result['duplicate']);
    }

    // ─── Test: backoff delay formula ──────────────────────────────────────────

    public function test_backoff_delay_matches_exponential_formula(): void
    {
        $webhook = Webhook::query()->create([
            'name' => 'Backoff',
            'url' => 'https://example.test/webhook',
            'events' => ['*'],
            'status' => 'active',
            'anti_replay_enabled' => false,
            'rotation_status' => 'current',
        ]);

        $delivery = WebhookDelivery::query()->create([
            'webhook_id' => $webhook->id,
            'event_type' => 'test.event',
            'payload' => [],
            'status' => 'retrying',
            'attempt_number' => 1,
            'max_attempts' => 5,
        ]);

        // Attempt 1: delay should be 2^1 * 60 = 120s
        $this->assertSame(120, WebhookDelivery::backoffDelaySeconds(1));
        // Attempt 2: delay should be 2^2 * 60 = 240s
        $this->assertSame(240, WebhookDelivery::backoffDelaySeconds(2));
        // Attempt 3: delay should be 2^3 * 60 = 480s
        $this->assertSame(480, WebhookDelivery::backoffDelaySeconds(3));
        // Attempt 6: would be 2^6 * 60 = 3840s but capped at 3600s
        $this->assertSame(3600, WebhookDelivery::backoffDelaySeconds(6));

        // Simulate failed attempt 1 → delivery should move to retrying with correct delay
        $delivery->markFailedWithRetry('HTTP 500', '500');
        $delivery->refresh();

        $this->assertSame('retrying', $delivery->status);
        $this->assertSame(2, $delivery->attempt_number);
        $this->assertNotNull($delivery->next_retry_at);
        // next_retry_at should be approximately 120s from now (attempt was 1 → delay = 2^1*60 = 120)
        $diffSecs = (int) now()->diffInSeconds($delivery->next_retry_at, false);
        $this->assertGreaterThan(100, $diffSecs);
        $this->assertLessThanOrEqual(130, $diffSecs);
    }

    // ─── Test: secret rotation accepts both old and new secret ────────────────

    public function test_secret_rotation_accepts_both_secrets(): void
    {
        $service = app(WebhookSecurityService::class);
        $currentSecret = 'old-secret-abc123';
        $newSecret = 'new-secret-xyz789';

        $webhook = Webhook::query()->create([
            'name' => 'Rotation',
            'url' => 'https://example.test/webhook',
            'events' => ['*'],
            'secret' => $currentSecret,
            'status' => 'active',
            'anti_replay_enabled' => false,
            'rotation_status' => 'current',
        ]);

        // Initiate rotation with a known new secret
        $service->initiateSecretRotation($webhook, $newSecret);
        $webhook->refresh();

        $this->assertSame('pending', $webhook->rotation_status);
        $this->assertNotNull($webhook->pending_secret);

        $payload = json_encode(['event' => 'test', 'data' => ['key' => 'value']]);

        // Signature with OLD secret should still be accepted during rotation
        $sigOld = 'sha256=' . hash_hmac('sha256', $payload, $currentSecret);
        $reqOld = Request::create('/', 'POST', [], [], [], [], $payload);
        $reqOld->headers->set('X-Catmin-Signature', $sigOld);
        $resultOld = $service->validateSignature($reqOld, $webhook, $payload);
        $this->assertTrue((bool) $resultOld['valid'], 'Old secret should be accepted during rotation');

        // Signature with NEW secret should also be accepted during rotation
        $sigNew = 'sha256=' . hash_hmac('sha256', $payload, $newSecret);
        $reqNew = Request::create('/', 'POST', [], [], [], [], $payload);
        $reqNew->headers->set('X-Catmin-Signature', $sigNew);
        $resultNew = $service->validateSignature($reqNew, $webhook, $payload);
        $this->assertTrue((bool) $resultNew['valid'], 'New (pending) secret should be accepted during rotation');

        // Complete rotation
        $service->completeSecretRotation($webhook);
        $webhook->refresh();

        $this->assertSame('current', $webhook->rotation_status);
        $this->assertSame($newSecret, $webhook->secret);
        $this->assertNull($webhook->pending_secret);

        // After rotation, only new secret should work
        $reqOldAfter = Request::create('/', 'POST', [], [], [], [], $payload);
        $reqOldAfter->headers->set('X-Catmin-Signature', $sigOld);
        $resultOldAfter = $service->validateSignature($reqOldAfter, $webhook, $payload);
        $this->assertFalse((bool) $resultOldAfter['valid'], 'Old secret should be rejected after rotation completes');
    }
}
