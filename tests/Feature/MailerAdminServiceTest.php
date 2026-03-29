<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Schema\Blueprint;
use Modules\Mailer\Jobs\SendTemplatedMailJob;
use Modules\Mailer\Mail\TemplatedMail;
use Modules\Mailer\Models\MailerConfig;
use Modules\Mailer\Models\MailerHistory;
use Modules\Mailer\Models\MailerTemplate;
use Modules\Mailer\Services\MailerAdminService;
use Modules\Logger\Models\SystemAlert;
use Tests\TestCase;

class MailerAdminServiceTest extends TestCase
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

        $this->createMailerTables();
    }

    public function test_preview_uses_sample_payload(): void
    {
        $template = MailerTemplate::query()->create([
            'code' => 'preview_test',
            'name' => 'Preview Test',
            'subject' => 'Bonjour {{ customer.name }}',
            'body_html' => '<p>Commande {{ order.number }}</p>',
            'body_text' => 'Commande {{ order.number }}',
            'available_variables' => ['customer.name', 'order.number'],
            'sample_payload' => [
                'customer' => ['name' => 'Alice'],
                'order' => ['number' => 'CMD-001'],
            ],
            'is_enabled' => true,
        ]);

        $preview = app(MailerAdminService::class)->previewTemplate($template);

        $this->assertSame('Bonjour Alice', $preview['subject']);
        $this->assertStringContainsString('Commande CMD-001', $preview['body_html']);
        $this->assertStringContainsString('Commande CMD-001', $preview['body_text']);
    }

    public function test_dispatch_template_sync_persists_sent_history(): void
    {
        Mail::fake();

        MailerConfig::query()->create([
            'driver' => 'smtp',
            'from_email' => 'no-reply@example.com',
            'from_name' => 'CATMIN',
            'reply_to_email' => 'support@example.com',
            'retry_max_attempts' => 3,
            'retry_backoff_seconds' => 60,
            'failure_alert_threshold' => 5,
            'is_enabled' => true,
        ]);

        $template = MailerTemplate::query()->create([
            'code' => 'sync_test',
            'name' => 'Sync Test',
            'subject' => 'Salut {{ user.name }}',
            'body_html' => '<p>Bienvenue {{ user.name }}</p>',
            'body_text' => 'Bienvenue {{ user.name }}',
            'available_variables' => ['user.name'],
            'sample_payload' => ['user' => ['name' => 'Demo']],
            'is_enabled' => true,
        ]);

        $history = app(MailerAdminService::class)->dispatchTemplate($template, 'dev@example.com', 'Dev', [
            'user' => ['name' => 'Dev'],
        ], [
            'queue' => false,
            'is_test' => true,
            'trigger_source' => 'phpunit.sync',
        ]);

        $this->assertSame('sent', $history->fresh()->status);
        $this->assertNotNull($history->fresh()->sent_at);
        $this->assertDatabaseHas('mailer_history', [
            'id' => $history->id,
            'recipient' => 'dev@example.com',
            'status' => 'sent',
        ]);

        Mail::assertSent(TemplatedMail::class, 1);
    }

    public function test_dispatch_template_queue_creates_queued_history(): void
    {
        Queue::fake();

        MailerConfig::query()->create([
            'driver' => 'smtp',
            'from_email' => 'no-reply@example.com',
            'retry_max_attempts' => 3,
            'retry_backoff_seconds' => 60,
            'failure_alert_threshold' => 5,
            'is_enabled' => true,
        ]);

        $template = MailerTemplate::query()->create([
            'code' => 'queue_test',
            'name' => 'Queue Test',
            'subject' => 'Queue {{ user.name }}',
            'body_html' => '<p>Queue {{ user.name }}</p>',
            'body_text' => 'Queue {{ user.name }}',
            'available_variables' => ['user.name'],
            'sample_payload' => ['user' => ['name' => 'Queue']],
            'is_enabled' => true,
        ]);

        $history = app(MailerAdminService::class)->dispatchTemplate($template, 'queue@example.com', null, [], [
            'queue' => true,
            'is_test' => true,
            'trigger_source' => 'phpunit.queue',
        ]);

        $this->assertSame('queued', $history->fresh()->status);
        $this->assertNotNull($history->fresh()->queued_at);
        Queue::assertPushed(SendTemplatedMailJob::class, 1);
    }

    public function test_dispatch_template_sandbox_redirects_recipient(): void
    {
        Mail::fake();

        MailerConfig::query()->create([
            'driver' => 'smtp',
            'from_email' => 'no-reply@example.com',
            'sandbox_mode' => true,
            'sandbox_recipient' => 'sandbox@example.com',
            'retry_max_attempts' => 3,
            'retry_backoff_seconds' => 60,
            'failure_alert_threshold' => 5,
            'is_enabled' => true,
        ]);

        $template = MailerTemplate::query()->create([
            'code' => 'sandbox_test',
            'name' => 'Sandbox Test',
            'subject' => 'Sandbox {{ user.name }}',
            'body_html' => '<p>Bonjour {{ user.name }}</p>',
            'body_text' => 'Bonjour {{ user.name }}',
            'available_variables' => ['user.name'],
            'sample_payload' => ['user' => ['name' => 'Sandbox']],
            'is_enabled' => true,
        ]);

        $history = app(MailerAdminService::class)->dispatchTemplate($template, 'real@example.com', 'Real User', [
            'user' => ['name' => 'Dev'],
        ], [
            'queue' => false,
            'is_test' => false,
            'trigger_source' => 'phpunit.sandbox',
        ]);

        $this->assertSame('sandbox@example.com', $history->fresh()->recipient);
        $this->assertTrue((bool) ($history->fresh()->variables_json['mail']['sandbox'] ?? false));
        Mail::assertSent(TemplatedMail::class, 1);
    }

    public function test_retryable_failure_moves_history_to_retrying_and_switches_to_fallback(): void
    {
        Queue::fake();

        MailerConfig::query()->create([
            'driver' => 'smtp',
            'from_email' => 'no-reply@example.com',
            'retry_max_attempts' => 3,
            'retry_backoff_seconds' => 30,
            'fallback_driver' => 'log',
            'failure_alert_threshold' => 10,
            'is_enabled' => true,
        ]);

        $template = MailerTemplate::query()->create([
            'code' => 'retryable_test',
            'name' => 'Retryable Test',
            'subject' => 'Retry',
            'body_html' => '<p>Retry</p>',
            'body_text' => 'Retry',
            'is_enabled' => true,
        ]);

        $history = MailerHistory::query()->create([
            'recipient' => 'retry@example.com',
            'subject' => 'Retry me',
            'template_code' => $template->code,
            'driver' => 'smtp',
            'status' => 'queued',
            'body_html' => '<p>Retry</p>',
            'body_text' => 'Retry',
            'attempts' => 0,
        ]);

        Mail::partialMock()
            ->shouldReceive('mailer')
            ->once()
            ->with('smtp')
            ->andReturn(new class {
                public function to(...$args): static { return $this; }
                public function send($mailable): void { throw new \RuntimeException('Temporary SMTP down'); }
            });

        $result = app(MailerAdminService::class)->deliverHistory($history);

        $this->assertSame('retrying', $result->fresh()->status);
        $this->assertNotNull($result->fresh()->next_retry_at);
        $this->assertSame('log', $result->fresh()->driver);
        Queue::assertPushed(SendTemplatedMailJob::class, 1);
    }

    public function test_terminal_failure_stays_failed_without_retry(): void
    {
        Queue::fake();
        Cache::flush();

        MailerConfig::query()->create([
            'driver' => 'smtp',
            'from_email' => 'no-reply@example.com',
            'retry_max_attempts' => 3,
            'retry_backoff_seconds' => 30,
            'failure_alert_threshold' => 10,
            'is_enabled' => true,
        ]);

        $history = MailerHistory::query()->create([
            'recipient' => 'bad@example.com',
            'subject' => 'Bad recipient',
            'template_code' => 'system_test',
            'driver' => 'smtp',
            'status' => 'queued',
            'body_html' => '<p>Oops</p>',
            'body_text' => 'Oops',
            'attempts' => 0,
        ]);

        Mail::partialMock()
            ->shouldReceive('mailer')
            ->once()
            ->with('smtp')
            ->andReturn(new class {
                public function to(...$args): static { return $this; }
                public function send($mailable): void { throw new \RuntimeException('550 invalid address'); }
            });

        $result = app(MailerAdminService::class)->deliverHistory($history);

        $this->assertSame('failed', $result->fresh()->status);
        $this->assertNull($result->fresh()->next_retry_at);
        Queue::assertNothingPushed();
    }

    public function test_manual_retry_requeues_failed_history(): void
    {
        Queue::fake();

        $history = MailerHistory::query()->create([
            'recipient' => 'failed@example.com',
            'subject' => 'Manual retry',
            'template_code' => 'system_test',
            'driver' => 'smtp',
            'status' => 'failed',
            'body_html' => '<p>Retry</p>',
            'body_text' => 'Retry',
            'attempts' => 2,
            'failed_at' => now(),
            'error_message' => 'Temporary SMTP down',
        ]);

        $result = app(MailerAdminService::class)->retryHistory($history);

        $this->assertSame('queued', $result->fresh()->status);
        $this->assertNull($result->fresh()->error_message);
        Queue::assertPushed(SendTemplatedMailJob::class, 1);
    }

    public function test_failure_threshold_creates_operational_alert(): void
    {
        Queue::fake();
        Cache::flush();

        MailerConfig::query()->create([
            'driver' => 'smtp',
            'from_email' => 'no-reply@example.com',
            'retry_max_attempts' => 1,
            'retry_backoff_seconds' => 30,
            'failure_alert_threshold' => 1,
            'is_enabled' => true,
        ]);

        $history = MailerHistory::query()->create([
            'recipient' => 'ops@example.com',
            'subject' => 'Alert me',
            'template_code' => 'system_test',
            'driver' => 'smtp',
            'status' => 'queued',
            'body_html' => '<p>Alert</p>',
            'body_text' => 'Alert',
            'attempts' => 0,
        ]);

        Mail::partialMock()
            ->shouldReceive('mailer')
            ->once()
            ->with('smtp')
            ->andReturn(new class {
                public function to(...$args): static { return $this; }
                public function send($mailable): void { throw new \RuntimeException('SMTP provider unavailable'); }
            });

        app(MailerAdminService::class)->deliverHistory($history);

        $this->assertDatabaseHas('system_alerts', [
            'alert_type' => 'mailer_failure',
            'title' => 'Mailer failures threshold reached',
        ]);

        $this->assertSame(1, SystemAlert::query()->where('alert_type', 'mailer_failure')->count());
    }

    private function createMailerTables(): void
    {
        Schema::dropAllTables();

        Schema::create('mailer_configs', function (Blueprint $table): void {
            $table->id();
            $table->string('driver', 64)->default('log');
            $table->string('from_email')->nullable();
            $table->string('from_name')->nullable();
            $table->string('reply_to_email')->nullable();
            $table->string('brand_name')->nullable();
            $table->string('brand_logo_url')->nullable();
            $table->string('brand_primary_color', 7)->default('#0d6efd');
            $table->text('brand_footer_text')->nullable();
            $table->boolean('sandbox_mode')->default(false);
            $table->string('sandbox_recipient')->nullable();
            $table->unsignedSmallInteger('retry_max_attempts')->default(3);
            $table->unsignedInteger('retry_backoff_seconds')->default(60);
            $table->string('fallback_driver', 64)->nullable();
            $table->unsignedSmallInteger('failure_alert_threshold')->default(5);
            $table->boolean('is_enabled')->default(false);
            $table->timestamps();
        });

        Schema::create('mailer_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 120)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('subject');
            $table->longText('body_html')->nullable();
            $table->longText('body_text')->nullable();
            $table->json('available_variables')->nullable();
            $table->json('sample_payload')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();
        });

        Schema::create('mailer_history', function (Blueprint $table): void {
            $table->id();
            $table->string('recipient');
            $table->string('recipient_name')->nullable();
            $table->string('subject');
            $table->string('template_code', 120)->nullable();
            $table->string('driver', 64)->nullable();
            $table->string('status', 32)->default('pending');
            $table->json('variables_json')->nullable();
            $table->longText('body_html')->nullable();
            $table->longText('body_text')->nullable();
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('next_retry_at')->nullable();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->boolean('is_test')->default(false);
            $table->string('trigger_source', 120)->nullable();
            $table->text('error_message')->nullable();
            $table->string('provider_message_id', 191)->nullable();
            $table->string('original_recipient')->nullable();
            $table->string('failure_class', 64)->nullable();
            $table->timestamps();
        });

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

        Schema::create('settings', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->string('label')->nullable();
            $table->text('value')->nullable();
            $table->string('type', 50)->default('string');
            $table->string('group')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_public')->default(false);
            $table->boolean('is_editable')->default(true);
            $table->text('options')->nullable();
            $table->text('validation_rules')->nullable();
            $table->timestamps();
        });
    }
}
