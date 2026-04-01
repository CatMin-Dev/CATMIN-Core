<?php

namespace Tests\Feature;

use App\Services\AnalyticsService;
use App\Services\SettingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AnalyticsServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!Schema::hasTable('analytics_events')) {
            $this->artisan('migrate', ['--force' => true]);
        }

        DB::table('analytics_events')->truncate();

        config([
            'catmin.analytics.enabled' => true,
            'catmin.analytics.anonymous_mode' => true,
            'catmin.analytics.retention_days' => 30,
            'catmin.analytics.modules_tracked' => ['*'],
            'catmin.settings.defaults.analytics.enabled' => true,
            'catmin.settings.defaults.analytics.anonymous_mode' => true,
            'catmin.settings.defaults.analytics.retention_days' => 30,
            'catmin.settings.defaults.analytics.modules_tracked' => ['*'],
        ]);
        DB::table('settings')->updateOrInsert(
            ['key' => 'analytics.enabled'],
            ['value' => '1', 'type' => 'boolean', 'group' => 'analytics', 'description' => 'enabled', 'is_public' => false, 'updated_at' => now(), 'created_at' => now()]
        );
        DB::table('settings')->updateOrInsert(
            ['key' => 'analytics.anonymous_mode'],
            ['value' => '1', 'type' => 'boolean', 'group' => 'analytics', 'description' => 'anonymous', 'is_public' => false, 'updated_at' => now(), 'created_at' => now()]
        );
        DB::table('settings')->updateOrInsert(
            ['key' => 'analytics.retention_days'],
            ['value' => '30', 'type' => 'integer', 'group' => 'analytics', 'description' => 'retention', 'is_public' => false, 'updated_at' => now(), 'created_at' => now()]
        );
        DB::table('settings')->updateOrInsert(
            ['key' => 'analytics.modules_tracked'],
            ['value' => json_encode(['*'], JSON_UNESCAPED_SLASHES), 'type' => 'json', 'group' => 'analytics', 'description' => 'tracked', 'is_public' => false, 'updated_at' => now(), 'created_at' => now()]
        );
        SettingService::forgetCache();
    }

    public function test_track_stores_privacy_safe_event_when_enabled(): void
    {
        app(AnalyticsService::class)->track(
            eventName: 'admin.module.opened',
            domain: 'admin',
            action: 'opened',
            status: 'success',
            context: [
                'query' => 'analytics',
                'password' => 'must-not-be-stored',
            ],
            metadata: [
                'results' => 4,
                'token' => 'must-not-be-stored',
            ]
        );

        $row = DB::table('analytics_events')->first();

        $this->assertNotNull($row);
        $this->assertSame('admin.module.opened', $row->event_name);
        $this->assertSame('admin', $row->domain);

        $context = json_decode((string) $row->context, true) ?: [];
        $metadata = json_decode((string) $row->metadata, true) ?: [];

        $this->assertArrayHasKey('query', $context);
        $this->assertArrayNotHasKey('password', $context);
        $this->assertArrayHasKey('results', $metadata);
        $this->assertArrayNotHasKey('token', $metadata);
    }

    public function test_track_is_disabled_when_setting_is_off(): void
    {
        config([
            'catmin.analytics.enabled' => false,
            'catmin.settings.defaults.analytics.enabled' => false,
        ]);
        DB::table('settings')->updateOrInsert(
            ['key' => 'analytics.enabled'],
            ['value' => '0', 'type' => 'boolean', 'group' => 'analytics', 'description' => 'enabled', 'is_public' => false, 'updated_at' => now(), 'created_at' => now()]
        );
        SettingService::forgetCache();

        app(AnalyticsService::class)->track('admin.module.opened', 'admin', 'opened');

        $this->assertSame(0, DB::table('analytics_events')->count());
    }

    public function test_prune_respects_retention_days(): void
    {
        DB::table('analytics_events')->insert([
            'event_name' => 'legacy.event',
            'domain' => 'admin',
            'action' => 'opened',
            'status' => 'success',
            'occurred_at' => now()->subDays(40),
            'created_at' => now()->subDays(40),
            'updated_at' => now()->subDays(40),
        ]);

        DB::table('analytics_events')->insert([
            'event_name' => 'fresh.event',
            'domain' => 'admin',
            'action' => 'opened',
            'status' => 'success',
            'occurred_at' => now()->subDays(2),
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ]);

        config([
            'catmin.analytics.retention_days' => 30,
            'catmin.settings.defaults.analytics.retention_days' => 30,
        ]);
        DB::table('settings')->updateOrInsert(
            ['key' => 'analytics.retention_days'],
            ['value' => '30', 'type' => 'integer', 'group' => 'analytics', 'description' => 'retention', 'is_public' => false, 'updated_at' => now(), 'created_at' => now()]
        );
        SettingService::forgetCache();

        $deleted = app(AnalyticsService::class)->prune();

        $this->assertSame(1, $deleted);
        $this->assertSame(1, DB::table('analytics_events')->count());
    }
}
