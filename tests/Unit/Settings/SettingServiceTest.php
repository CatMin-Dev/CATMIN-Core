<?php

namespace Tests\Unit\Settings;

use App\Models\Setting;
use App\Services\SettingService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SettingServiceTest extends TestCase
{
    private function createTables(): void
    {
        \Illuminate\Support\Facades\Schema::dropAllTables();

        \Illuminate\Support\Facades\DB::statement('CREATE TABLE IF NOT EXISTS settings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            key VARCHAR(191) NOT NULL UNIQUE,
            label VARCHAR(191),
            value TEXT,
            type VARCHAR(50) DEFAULT "string",
            "group" VARCHAR(100),
            description TEXT,
            is_public BOOLEAN DEFAULT 0,
            is_editable BOOLEAN DEFAULT 1,
            options TEXT,
            validation_rules VARCHAR(500),
            created_at DATETIME,
            updated_at DATETIME
        )');

        \Illuminate\Support\Facades\DB::statement('CREATE TABLE IF NOT EXISTS webhooks (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(191),
            url TEXT NOT NULL,
            secret VARCHAR(191),
            events TEXT,
            status VARCHAR(50) DEFAULT "active",
            created_at DATETIME,
            updated_at DATETIME,
            last_triggered_at DATETIME,
            last_delivery_at DATETIME,
            last_delivery_status INTEGER,
            last_delivery_error TEXT
        )');
    }

    protected function setUp(): void
    {
        parent::setUp();

        if (!\extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('pdo_sqlite extension is not available.');
        }

        \Illuminate\Support\Facades\Config::set('database.default', 'sqlite');
        \Illuminate\Support\Facades\Config::set('database.connections.sqlite.database', ':memory:');

        $this->createTables();
        SettingService::forgetCache();
    }

    public function test_put_and_get_string_value(): void
    {
        SettingService::put('test.key', 'hello', 'string', 'test', 'Test value', false, 'Test label');
        $this->assertSame('hello', SettingService::get('test.key'));
    }

    public function test_get_returns_default_when_missing(): void
    {
        $result = SettingService::get('missing.key', 'default_val');
        $this->assertSame('default_val', $result);
    }

    public function test_group_returns_only_matching_keys(): void
    {
        SettingService::put('ops.alert_email', 'alert@test.com', 'email', 'ops', '', false);
        SettingService::put('ops.log_retention_days', '30', 'integer', 'ops', '', false);
        SettingService::put('mailer.from_email', 'from@test.com', 'email', 'mailer', '', false);

        $ops = SettingService::group('ops');
        $this->assertTrue($ops->has('ops.alert_email'));
        $this->assertTrue($ops->has('ops.log_retention_days'));
        $this->assertFalse($ops->has('mailer.from_email'));
    }

    public function test_cache_is_invalidated_after_put(): void
    {
        SettingService::put('cache.test', 'v1', 'string', 'test', '', false);
        $this->assertSame('v1', SettingService::get('cache.test'));

        SettingService::put('cache.test', 'v2', 'string', 'test', '', false);
        $this->assertSame('v2', SettingService::get('cache.test'));
    }

    public function test_defaults_are_respected_when_db_empty(): void
    {
        \Illuminate\Support\Facades\Config::set('catmin.settings.defaults', ['site.name' => 'DefaultSite']);
        SettingService::forgetCache();

        $this->assertSame('DefaultSite', SettingService::get('site.name'));
    }
}
