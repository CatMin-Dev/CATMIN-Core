<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class CatminAuditRbacCommandTest extends TestCase
{
    public function test_audit_rbac_command_returns_structured_json(): void
    {
        $exitCode = Artisan::call('catmin:audit-rbac', [
            '--json' => true,
        ]);

        $payload = json_decode(Artisan::output(), true);

        $this->assertContains($exitCode, [0, 1]);
        $this->assertIsArray($payload);
        $this->assertArrayHasKey('summary', $payload);
        $this->assertArrayHasKey('sensitive_protected', $payload);
        $this->assertArrayHasKey('sensitive_unprotected', $payload);
        $this->assertArrayHasKey('inconsistent_permissions', $payload);
        $this->assertArrayHasKey('sensitive_coverage_percent', $payload['summary']);
    }
}
