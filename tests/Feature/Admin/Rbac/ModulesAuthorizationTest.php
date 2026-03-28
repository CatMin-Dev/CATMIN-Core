<?php

namespace Tests\Feature\Admin\Rbac;

use Tests\TestCase;

class ModulesAuthorizationTest extends TestCase
{
    public function test_modules_index_denies_without_list_permission(): void
    {
        $response = $this->withAdminPermissions([])->get('/admin/modules');
        $response->assertForbidden();
    }

    public function test_modules_index_allows_with_list_permission(): void
    {
        $response = $this->withAdminPermissions(['module.core.list'])->get('/admin/modules');
        $this->assertNotSame(403, $response->getStatusCode());
    }

    public function test_module_enable_denies_without_config_permission(): void
    {
        $response = $this->withAdminPermissions(['module.core.list'])->post('/admin/modules/core/enable');
        $response->assertForbidden();
    }

    public function test_module_enable_allows_super_admin_bypass(): void
    {
        $response = $this->withAdminPermissions(['*'])->post('/admin/modules/core/enable');
        $this->assertNotSame(403, $response->getStatusCode());
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
