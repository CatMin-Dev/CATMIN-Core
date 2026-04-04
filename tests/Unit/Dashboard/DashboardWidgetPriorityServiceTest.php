<?php

namespace Tests\Unit\Dashboard;

use App\Services\Dashboard\DashboardWidgetPriorityService;
use Tests\TestCase;

class DashboardWidgetPriorityServiceTest extends TestCase
{
    public function test_it_normalizes_zone_priority_and_span(): void
    {
        $service = new DashboardWidgetPriorityService();

        $widgets = $service->normalize([
            ['title' => 'A', 'tone' => 'danger', 'order' => 10],
            ['title' => 'B', 'zone' => 'actions', 'priority' => 5, 'span' => 'full'],
            ['title' => 'C', 'tone' => 'info', 'span' => 'invalid'],
        ]);

        $byTitle = collect($widgets)->keyBy('title');

        $this->assertSame('critical', $byTitle['A']['zone']);
        $this->assertSame('full', $byTitle['B']['span']);
        $this->assertSame('half', $byTitle['C']['span']);
    }
}
