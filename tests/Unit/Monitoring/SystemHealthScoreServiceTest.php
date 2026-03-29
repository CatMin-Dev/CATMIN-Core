<?php

namespace Tests\Unit\Monitoring;

use App\Services\SystemHealthScoreService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SystemHealthScoreServiceTest extends TestCase
{
    #[Test]
    public function it_builds_weighted_score_and_recommendations(): void
    {
        $service = app(SystemHealthScoreService::class);

        $result = $service->build([
            [
                'domain' => 'security',
                'status' => 'critical',
                'title' => 'Guardrails securite production',
                'message' => 'critical=1, warning=0 sur 7 check(s)',
                'metric' => 1,
                'threshold' => 0,
                'actions' => [['label' => 'Ouvrir Parametres', 'url' => 'https://example.test/settings']],
            ],
            [
                'domain' => 'queue',
                'status' => 'warning',
                'title' => 'Failed jobs',
                'message' => 'failed_jobs=3, seuil_warning=5',
                'metric' => 3,
                'threshold' => 5,
                'actions' => [['label' => 'Ouvrir Queue', 'url' => 'https://example.test/queue']],
            ],
            [
                'domain' => 'api',
                'status' => 'critical',
                'title' => 'API interne',
                'message' => 'ignoree dans le score',
                'metric' => 1,
                'threshold' => 0,
                'actions' => [],
            ],
        ]);

        $this->assertSame(77, $result['score']);
        $this->assertSame('critical', $result['status']);
        $this->assertSame('Stable', $result['label']);
        $this->assertCount(2, $result['recommendations']);
        $this->assertSame('Guardrails securite production', $result['recommendations'][0]['title']);
        $this->assertSame(0, collect($result['domains'])->firstWhere('domain', 'api')['penalty']);
    }

    #[Test]
    public function it_computes_trend_from_snapshot_history(): void
    {
        $service = app(SystemHealthScoreService::class);

        $result = $service->build([
            [
                'domain' => 'queue',
                'status' => 'ok',
                'title' => 'Failed jobs',
                'message' => 'RAS',
                'metric' => 0,
                'threshold' => 5,
                'actions' => [],
            ],
        ], [
            ['status' => 'warning', 'score' => 60],
        ]);

        $this->assertSame('up', $result['trend']['direction']);
        $this->assertSame(40, $result['trend']['delta']);
    }
}
