<?php

namespace Tests\Unit\Dashboard;

use App\Services\Dashboard\DashboardWidgetRegistry;
use Tests\TestCase;

class DashboardWidgetRegistryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DashboardWidgetRegistry::flush();
    }

    protected function tearDown(): void
    {
        DashboardWidgetRegistry::flush();

        parent::tearDown();
    }

    public function test_collect_sorts_widgets_by_order_and_title(): void
    {
        DashboardWidgetRegistry::register(function (): array {
            return [
                [
                    'id' => 'zeta',
                    'title' => 'Zeta',
                    'order' => 20,
                ],
                [
                    'id' => 'alpha',
                    'title' => 'Alpha',
                    'order' => 20,
                ],
            ];
        }, 50);

        DashboardWidgetRegistry::register(function (): array {
            return [
                'id' => 'first',
                'title' => 'First',
                'order' => 10,
            ];
        }, 10);

        $widgets = DashboardWidgetRegistry::collect();

        $this->assertCount(3, $widgets);
        $this->assertSame('first', $widgets[0]['id']);
        $this->assertSame('alpha', $widgets[1]['id']);
        $this->assertSame('zeta', $widgets[2]['id']);
    }

    public function test_collect_normalizes_non_list_payload(): void
    {
        DashboardWidgetRegistry::register(function (): array {
            return [
                'id' => 'single',
                'title' => 'Single',
                'items' => ['invalid-shape'],
            ];
        });

        $widgets = DashboardWidgetRegistry::collect();

        $this->assertCount(1, $widgets);
        $this->assertSame('single', $widgets[0]['id']);
        $this->assertSame('Single', $widgets[0]['title']);
        $this->assertSame([], $widgets[0]['items']);
        $this->assertSame('list', $widgets[0]['type']);
        $this->assertSame('secondary', $widgets[0]['tone']);
    }
}
