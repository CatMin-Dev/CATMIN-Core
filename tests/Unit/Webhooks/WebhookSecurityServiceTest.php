<?php

namespace Tests\Unit\Webhooks;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Modules\Webhooks\Models\Webhook;
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
    }
}
