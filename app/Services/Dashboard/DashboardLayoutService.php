<?php

namespace App\Services\Dashboard;

class DashboardLayoutService
{
    public function __construct(
        private readonly DashboardZoneRegistry $zoneRegistry,
        private readonly DashboardWidgetPriorityService $priorityService,
    ) {
    }

    /**
     * @param array<int,array<string,mixed>> $widgets
     * @param array<string,mixed> $dashboard
     * @return array<string,mixed>
     */
    public function build(array $widgets, array $dashboard): array
    {
        $normalizedWidgets = $this->priorityService->normalize($widgets);
        $zones = [];

        foreach ($this->zoneRegistry->zones() as $zone) {
            $zones[$zone['id']] = [
                'meta' => $zone,
                'widgets' => array_values(array_filter($normalizedWidgets, fn (array $w) => ($w['zone'] ?? 'secondary') === $zone['id'])),
            ];
        }

        return [
            'zones' => $zones,
            'kpis' => (array) ($dashboard['kpis'] ?? []),
            'quick_actions' => (array) ($dashboard['quick_actions'] ?? []),
            'alerts' => (array) ($dashboard['alerts'] ?? []),
            'charts' => (array) ($dashboard['charts'] ?? []),
            'module_health' => (array) ($dashboard['module_health'] ?? []),
        ];
    }
}
