<?php

namespace Tests\Unit\Settings;

use App\Services\SettingService;
use Modules\Settings\Services\SettingsAdminService;
use Tests\TestCase;

class SettingsAdminServiceTest extends TestCase
{
    private function createTables(): void
    {
        \Illuminate\Support\Facades\DB::statement('CREATE TABLE IF NOT EXISTS settings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            key VARCHAR(191) NOT NULL UNIQUE,
            label VARCHAR(191),
            value TEXT,
            type VARCHAR(50) DEFAULT "string",
            group_name VARCHAR(100),
            description TEXT,
            is_public BOOLEAN DEFAULT 0,
            is_editable BOOLEAN DEFAULT 1,
            options TEXT,
            validation_rules VARCHAR(500),
            created_at DATETIME,
            updated_at DATETIME
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

    public function test_ops_panel_returns_fallback_defaults(): void
    {
        $service = new SettingsAdminService();
        $ops = $service->opsPanel();

        $this->assertArrayHasKey('ops_log_retention_days', $ops);
        $this->assertIsInt($ops['ops_log_retention_days']);
        $this->assertGreaterThan(0, $ops['ops_log_retention_days']);
    }

    public function test_update_ops_panel_persists_values(): void
    {
        $service = new SettingsAdminService();
        $service->updateOpsPanel([
            'ops_alert_email'                => 'admin@example.com',
            'ops_alert_webhook_url'          => 'https://hook.example.com/alert',
            'ops_log_retention_days'         => 30,
            'ops_log_archive_days'           => 120,
            'ops_failed_jobs_threshold'      => 10,
            'ops_webhook_failures_threshold' => 5,
        ]);

        $this->assertSame('admin@example.com', SettingService::get('ops.alert_email'));
        $this->assertSame('30', SettingService::get('ops.log_retention_days'));
    }

    public function test_security_panel_defaults_are_sane(): void
    {
        $service = new SettingsAdminService();
        $security = $service->securityPanel();

        $this->assertGreaterThan(0, $security['security_login_lock_attempts']);
        $this->assertGreaterThan(0, $security['security_login_lock_minutes']);
        $this->assertGreaterThan(0, $security['security_password_reset_expiry']);
    }
}
