<?php

namespace Tests\Unit\Performance;

use App\Services\Performance\PerformanceBudgetService;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PerformanceBudgetServiceTest extends TestCase
{
    #[Test]
    public function it_resolves_budget_from_request_route_or_path(): void
    {
        config()->set('catmin.performance.budgets', [
            [
                'key' => 'admin.dashboard',
                'label' => 'Dashboard',
                'route' => 'admin.index',
                'target_response_ms' => 300,
                'max_response_ms' => 600,
                'max_queries' => 10,
                'max_slow_queries' => 0,
            ],
            [
                'key' => 'api.v2.pages',
                'label' => 'API Pages',
                'path' => 'api/v2/pages/published',
                'target_response_ms' => 250,
                'max_response_ms' => 500,
                'max_queries' => 6,
                'max_slow_queries' => 0,
            ],
        ]);

        $service = app(PerformanceBudgetService::class);

        $request = Request::create('/admin');
        $request->setRouteResolver(fn () => new class {
            public function getName(): string
            {
                return 'admin.index';
            }
        });

        $dashboard = $service->budgetForRequest($request);
        $api = $service->budgetForContext(['path' => 'api/v2/pages/published']);

        $this->assertSame('admin.dashboard', $dashboard['key']);
        $this->assertSame('api.v2.pages', $api['key']);
    }

    #[Test]
    public function it_detects_budget_breaches_for_duration_and_query_counts(): void
    {
        $service = app(PerformanceBudgetService::class);
        $budget = [
            'key' => 'admin.dashboard',
            'label' => 'Dashboard',
            'category' => 'admin',
            'route' => 'admin.index',
            'path' => '',
            'target_response_ms' => 300,
            'max_response_ms' => 600,
            'max_queries' => 10,
            'max_slow_queries' => 0,
            'notes' => '',
        ];

        $evaluation = $service->evaluate($budget, [
            'duration_ms' => 780,
            'query_count' => 14,
            'slow_query_count' => 1,
        ]);

        $this->assertTrue($evaluation['is_breach']);
        $this->assertSame(['response_ms', 'query_count', 'slow_query_count'], $evaluation['breaches']);
    }
}