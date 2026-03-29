<?php

namespace Tests\Unit\Security;

use App\Services\SecurityHardeningService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SecurityHardeningServiceTest extends TestCase
{
    #[Test]
    public function it_flags_app_debug_in_production_as_critical(): void
    {
        config()->set('app.env', 'production');
        config()->set('app.debug', true);

        $checks = collect(app(SecurityHardeningService::class)->collectGuardrails());
        $debugCheck = (array) $checks->firstWhere('key', 'app_debug_prod');

        $this->assertSame('critical', $debugCheck['status'] ?? null);
    }

    #[Test]
    public function it_marks_strong_internal_and_webhook_secrets_as_ok(): void
    {
        config()->set('app.env', 'local');
        config()->set('catmin.api.internal_token', 'Int3rnal.Token.With.Strong.Length');
        config()->set('catmin.webhooks.incoming_secret', 'Webh00k.Secret.With.Strong.Length');

        $checks = collect(app(SecurityHardeningService::class)->collectGuardrails());

        $this->assertSame('ok', data_get($checks->firstWhere('key', 'internal_api_token'), 'status'));
        $this->assertSame('ok', data_get($checks->firstWhere('key', 'webhook_incoming_secret'), 'status'));
    }

    #[Test]
    public function install_check_keeps_warning_non_blocking_outside_production(): void
    {
        config()->set('app.env', 'local');
        config()->set('app.debug', false);
        config()->set('catmin.admin.password', 'admin12345');
        config()->set('catmin.api.internal_token', 'weak');
        config()->set('catmin.webhooks.incoming_secret', '');
        config()->set('session.secure', false);

        $report = app(SecurityHardeningService::class)->installCheck();

        $this->assertTrue($report['ok']);
        $this->assertGreaterThan(0, (int) $report['warning_count']);
        $this->assertSame(0, (int) $report['critical_count']);
    }
}
