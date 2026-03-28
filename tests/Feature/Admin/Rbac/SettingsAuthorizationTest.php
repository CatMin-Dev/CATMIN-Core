<?php

namespace Tests\Feature\Admin\Rbac;

use Tests\TestCase;

class SettingsAuthorizationTest extends TestCase
{
    public function test_settings_index_denies_without_permission(): void
    {
        $response = $this->withAdminPermissions([])->get('/admin/settings');
        $response->assertForbidden();
    }

    public function test_settings_index_allows_with_permission(): void
    {
        $response = $this->withAdminPermissions(['module.settings.list'])->get('/admin/settings');
        $this->assertContains($response->getStatusCode(), [200, 302]);
    }

    private function withAdminPermissions(array $permissions): self
    {
        return $this->withSession([
            'catmin_admin_authenticated' => true,
            'catmin_admin_login_at' => now()->timestamp,
            'catmin_admin_username' => 'rbac-test',
            'catmin_rbac_permissions' => $permissions,
            'catmin_rbac_roles' => [],
        ]);
    }
}
