<?php

namespace Tests\Unit\Notifications;

use App\Services\Notifications\AdminNotificationService;
use App\Services\Notifications\NotificationAggregationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Modules\Logger\Models\MonitoringIncident;
use Modules\Notifications\Models\AdminNotification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminNotificationServiceTest extends TestCase
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

    #[Test]
    public function it_creates_a_simple_notification(): void
    {
        $notif = AdminNotificationService::notify(
            title: 'Test notification',
            message: 'Something happened.',
            type: 'info',
            source: 'system',
        );

        $this->assertInstanceOf(AdminNotification::class, $notif);
        $this->assertSame('info', $notif->type);
        $this->assertSame('Test notification', $notif->title);
        $this->assertFalse($notif->is_read);
        $this->assertFalse($notif->is_acknowledged);

        $this->assertSame(1, AdminNotification::query()->count());
    }

    #[Test]
    public function it_deduplicates_notifications_by_key_within_24h(): void
    {
        AdminNotificationService::notify(
            title: 'Alert once',
            message: 'First.',
            type: 'warning',
            source: 'monitoring',
            dedupeKey: 'test.dedup.key.1',
        );

        AdminNotificationService::notify(
            title: 'Alert again same key',
            message: 'Second – should be skipped.',
            type: 'warning',
            source: 'monitoring',
            dedupeKey: 'test.dedup.key.1',
        );

        $this->assertSame(1, AdminNotification::query()->count());
        $this->assertSame('Alert once', AdminNotification::query()->first()->title);
    }

    #[Test]
    public function it_allows_duplicate_notifications_without_dedupe_key(): void
    {
        AdminNotificationService::notify(title: 'Duplicate', message: 'A', type: 'info', source: 'system');
        AdminNotificationService::notify(title: 'Duplicate', message: 'B', type: 'info', source: 'system');

        $this->assertSame(2, AdminNotification::query()->count());
    }

    #[Test]
    public function it_marks_a_notification_as_read(): void
    {
        $notif = AdminNotificationService::notify(title: 'To read', message: 'body', type: 'info', source: 'system');

        $this->assertFalse($notif->is_read);

        AdminNotificationService::markRead($notif);
        $notif->refresh();

        $this->assertTrue($notif->is_read);
        $this->assertFalse($notif->is_acknowledged);
    }

    #[Test]
    public function it_acknowledges_a_notification(): void
    {
        $notif = AdminNotificationService::notify(title: 'To ack', message: 'body', type: 'critical', source: 'monitoring');

        AdminNotificationService::acknowledge($notif);
        $notif->refresh();

        $this->assertTrue($notif->is_acknowledged);
        $this->assertTrue($notif->is_read);
        $this->assertNotNull($notif->acknowledged_at);
    }

    #[Test]
    public function it_bulk_marks_as_read(): void
    {
        $n1 = AdminNotificationService::notify(title: 'N1', message: 'm', type: 'info', source: 'system');
        $n2 = AdminNotificationService::notify(title: 'N2', message: 'm', type: 'warning', source: 'system');
        $n3 = AdminNotificationService::notify(title: 'N3', message: 'm', type: 'warning', source: 'system');

        $count = AdminNotificationService::bulkRead([$n1->id, $n2->id]);

        $this->assertSame(2, $count);
        $n1->refresh();
        $n2->refresh();
        $n3->refresh();
        $this->assertTrue($n1->is_read);
        $this->assertTrue($n2->is_read);
        $this->assertFalse($n3->is_read);
    }

    #[Test]
    public function it_filters_listing_by_type_and_read_state(): void
    {
        AdminNotificationService::notify(title: 'Crit 1', message: 'm', type: 'critical', source: 'monitoring');
        AdminNotificationService::notify(title: 'Crit 2', message: 'm', type: 'critical', source: 'monitoring');
        AdminNotificationService::notify(title: 'Info 1', message: 'm', type: 'info', source: 'system');

        AdminNotification::query()->where('title', 'Crit 1')->update(['is_read' => true]);

        $critUnread = AdminNotificationService::listing(['type' => 'critical', 'read' => 'unread'], 25);
        $this->assertSame(1, $critUnread->total());

        $all = AdminNotificationService::listing([], 25);
        $this->assertSame(3, $all->total());
    }

    #[Test]
    public function it_generates_notification_from_open_monitoring_incident(): void
    {
        MonitoringIncident::query()->create([
            'title' => 'DB query slow',
            'message' => 'Query taking over 5s',
            'domain' => 'database',
            'status' => 'critical',
            'first_seen_at' => now()->subMinutes(30),
            'last_seen_at' => now()->subMinutes(5),
            'occurrence_count' => 3,
            'severity' => 'critical',
        ]);

        NotificationAggregationService::fromOpenIncidents();

        $notif = AdminNotification::query()->where('source', 'monitoring')->first();

        $this->assertNotNull($notif);
        $this->assertSame('critical', $notif->type);
        $this->assertStringContainsString('CRITICAL', $notif->title);
    }

    #[Test]
    public function it_deduplicates_from_repeated_aggregation(): void
    {
        MonitoringIncident::query()->create([
            'title' => 'Storage full',
            'message' => 'Disk at 99%',
            'domain' => 'storage',
            'status' => 'critical',
            'first_seen_at' => now()->subHour(),
            'last_seen_at' => now()->subMinutes(2),
            'occurrence_count' => 5,
            'severity' => 'critical',
        ]);

        NotificationAggregationService::fromOpenIncidents();
        NotificationAggregationService::fromOpenIncidents();

        $count = AdminNotification::query()->where('source', 'monitoring')->count();
        $this->assertSame(1, $count);
    }

    #[Test]
    public function it_purges_expired_notifications(): void
    {
        AdminNotificationService::notify(
            title: 'Old notif',
            message: 'expired',
            type: 'info',
            source: 'system',
            ttlMinutes: -1,
        );

        AdminNotificationService::notify(
            title: 'Fresh notif',
            message: 'still valid',
            type: 'info',
            source: 'system',
            ttlMinutes: 60,
        );

        $purged = AdminNotificationService::purgeExpired();

        $this->assertSame(1, $purged);
        $this->assertSame(1, AdminNotification::query()->count());
        $this->assertSame('Fresh notif', AdminNotification::query()->first()->title);
    }

    #[Test]
    public function it_does_not_send_email_below_threshold(): void
    {
        Mail::fake();

        Config::set('catmin.admin.username', 'admin');

        AdminNotificationService::critical(
            title: 'Single critical',
            message: 'only one',
            source: 'monitoring',
        );

        Mail::assertNothingSent();
    }

    private function createTables(): void
    {
        Schema::dropAllTables();

        Schema::create('admin_notifications', function (Blueprint $table): void {
            $table->id();
            $table->string('type', 20)->default('info');
            $table->string('source', 60)->nullable();
            $table->string('title', 255);
            $table->text('message');
            $table->string('action_url', 512)->nullable();
            $table->string('action_label', 80)->nullable();
            $table->string('dedupe_key', 128)->nullable();
            $table->boolean('is_read')->default(false);
            $table->boolean('is_acknowledged')->default(false);
            $table->unsignedBigInteger('user_id')->nullable();
            $table->json('context')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamps();
        });

        Schema::create('monitoring_incidents', function (Blueprint $table): void {
            $table->id();
            $table->string('title', 255);
            $table->text('message')->nullable();
            $table->string('domain', 80)->nullable();
            $table->string('status', 30)->default('warning');
            $table->string('severity', 30)->default('warning');
            $table->integer('occurrence_count')->default(1);
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 255)->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }
}
