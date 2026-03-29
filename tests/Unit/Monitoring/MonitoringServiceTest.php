<?php

namespace Tests\Unit\Monitoring;

use App\Services\MonitoringService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MonitoringServiceTest extends TestCase
{
    #[Test]
    public function it_computes_global_status_from_worst_check(): void
    {
        $checks = [
            ['status' => 'ok'],
            ['status' => 'warning'],
            ['status' => 'critical'],
        ];

        $this->assertSame('critical', MonitoringService::computeGlobalStatus($checks));
    }

    #[Test]
    public function it_computes_score_with_status_penalties(): void
    {
        $checks = [
            ['status' => 'ok'],
            ['status' => 'warning'],
            ['status' => 'degraded'],
        ];

        $this->assertSame(80, MonitoringService::computeScore($checks));
    }

    #[Test]
    public function it_classifies_status_by_thresholds(): void
    {
        $this->assertSame('ok', MonitoringService::classifyByThreshold(0, 2, 4, 8));
        $this->assertSame('warning', MonitoringService::classifyByThreshold(2, 2, 4, 8));
        $this->assertSame('degraded', MonitoringService::classifyByThreshold(4, 2, 4, 8));
        $this->assertSame('critical', MonitoringService::classifyByThreshold(8, 2, 4, 8));
    }

    #[Test]
    public function it_correlates_rows_by_domain_title_and_severity(): void
    {
        $rows = [
            ['domain' => 'queue', 'title' => 'Failed jobs', 'severity' => 'warning', 'message' => 'a'],
            ['domain' => 'queue', 'title' => 'Failed jobs', 'severity' => 'warning', 'message' => 'b'],
            ['domain' => 'queue', 'title' => 'Failed jobs', 'severity' => 'critical', 'message' => 'c'],
        ];

        $clusters = MonitoringService::correlateRows($rows);

        $this->assertCount(2, $clusters);

        $warning = collect($clusters)->firstWhere('severity', 'warning');
        $critical = collect($clusters)->firstWhere('severity', 'critical');

        $this->assertSame(2, $warning['occurrences']);
        $this->assertSame(1, $critical['occurrences']);
    }
}
