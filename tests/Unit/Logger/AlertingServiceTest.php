<?php

namespace Tests\Unit\Logger;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Modules\Logger\Services\AlertingService;
use Tests\TestCase;

class AlertingServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('pdo_sqlite extension is not available.');
        }

        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', ':memory:');
        Config::set('catmin.alerting.email_to', '');
        Config::set('catmin.alerting.webhook_url', '');

        app('db')->purge('sqlite');
        app('db')->reconnect('sqlite');

        $this->createTables();
    }

    public function test_creates_critical_webhook_failed_alert(): void
    {
        $service = app(AlertingService::class);

        $alert = $service->alertWebhookFailed(10, 'https://example.test/hook', 'timeout', 500);

        $this->assertSame('webhook_failed', $alert->alert_type);
        $this->assertSame('critical', $alert->severity);
        $this->assertFalse((bool) $alert->acknowledged);
    }

    private function createTables(): void
    {
        Schema::dropAllTables();

        Schema::create('system_alerts', function (Blueprint $table): void {
            $table->id();
            $table->string('alert_type', 100);
            $table->string('severity', 20)->default('warning');
            $table->string('title', 255);
            $table->text('message');
            $table->json('context')->nullable();
            $table->boolean('acknowledged')->default(false);
            $table->timestamp('acknowledged_at')->nullable();
            $table->string('acknowledged_by')->nullable();
            $table->boolean('notified')->default(false);
            $table->timestamp('notified_at')->nullable();
            $table->string('notification_channels', 255)->nullable();
            $table->timestamps();
        });
    }
}
