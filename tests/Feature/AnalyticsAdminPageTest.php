<?php

namespace Tests\Feature;

use App\Services\SettingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AnalyticsAdminPageTest extends TestCase
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
            'catmin.settings.defaults.analytics.enabled' => true,
        ]);
        DB::table('settings')->updateOrInsert(
            ['key' => 'analytics.enabled'],
            ['value' => '1', 'type' => 'boolean', 'group' => 'analytics', 'description' => 'enabled', 'is_public' => false, 'updated_at' => now(), 'created_at' => now()]
        );
        SettingService::forgetCache();
    }

    public function test_admin_analytics_page_renders(): void
    {
        $this->withSession([
            'catmin_admin_authenticated' => true,
            'catmin_admin_username' => 'admin',
            'catmin_rbac_permissions' => ['*'],
            'catmin_rbac_roles' => ['super-admin'],
        ]);

        $response = $this->get('/admin/analytics');

        $response->assertOk();
        $response->assertSee('Analytics internes');
        $response->assertSee('Configuration analytics');
    }
}
