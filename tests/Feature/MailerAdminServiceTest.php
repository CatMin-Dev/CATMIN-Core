<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Modules\Mailer\Jobs\SendTemplatedMailJob;
use Modules\Mailer\Mail\TemplatedMail;
use Modules\Mailer\Models\MailerConfig;
use Modules\Mailer\Models\MailerHistory;
use Modules\Mailer\Models\MailerTemplate;
use Modules\Mailer\Services\MailerAdminService;
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

    private function createMailerTables(): void
    {
        Schema::dropAllTables();

        Schema::create('mailer_configs', function (Blueprint $table): void {
            $table->id();
            $table->string('driver', 64)->default('log');
            $table->string('from_email')->nullable();
            $table->string('from_name')->nullable();
            $table->string('reply_to_email')->nullable();
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
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->boolean('is_test')->default(false);
            $table->string('trigger_source', 120)->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }
}
