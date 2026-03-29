<?php

namespace Tests\Unit\System;

use App\Services\EnvironmentValidatorService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EnvironmentValidatorServiceTest extends TestCase
{
    #[Test]
    public function it_blocks_when_production_runs_with_debug_enabled(): void
    {
        config()->set('app.env', 'production');
        config()->set('app.debug', true);

        $report = app(EnvironmentValidatorService::class)->run();

        $this->assertTrue($report['blocked']);
        $this->assertTrue($report['summary']['error'] >= 1);

        $debugItem = collect($report['items'])->firstWhere('key', 'app_env_debug');
        $this->assertNotNull($debugItem);
        $this->assertSame('ERROR', $debugItem['status']);
        $this->assertTrue($debugItem['critical']);
    }

    #[Test]
    public function it_marks_queue_sync_as_warning_with_clear_recommendation(): void
    {
        config()->set('app.env', 'local');
        config()->set('app.debug', false);
        config()->set('queue.default', 'sync');
        config()->set('app.key', 'base64:' . base64_encode(random_bytes(32)));

        $report = app(EnvironmentValidatorService::class)->run();

        $queueItem = collect($report['items'])->firstWhere('key', 'queue_active');
        $this->assertNotNull($queueItem);
        $this->assertSame('WARNING', $queueItem['status']);
        $this->assertStringContainsString('worker', (string) $queueItem['recommendation']);
    }
}
