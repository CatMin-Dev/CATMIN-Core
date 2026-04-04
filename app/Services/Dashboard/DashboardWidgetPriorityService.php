<?php

namespace App\Services\Dashboard;

class DashboardWidgetPriorityService
{
    /**
     * @param array<int,array<string,mixed>> $widgets
     * @return array<int,array<string,mixed>>
     */
    public function normalize(array $widgets): array
    {
        return collect($widgets)
            ->map(function (array $widget): array {
                $zone = (string) ($widget['zone'] ?? $this->zoneFromTone((string) ($widget['tone'] ?? 'secondary')));
                $priority = (int) ($widget['priority'] ?? $widget['order'] ?? 100);
                $span = (string) ($widget['span'] ?? 'half');

                $widget['zone'] = $zone;
                $widget['priority'] = $priority;
                $widget['span'] = in_array($span, ['half', 'full'], true) ? $span : 'half';

                return $widget;
            })
            ->sortBy([
                ['zone', 'asc'],
                ['priority', 'asc'],
                ['title', 'asc'],
            ])
            ->values()
            ->all();
    }

    private function zoneFromTone(string $tone): string
    {
        return match ($tone) {
            'danger' => 'critical',
            'warning' => 'activity',
            'info' => 'secondary',
            default => 'secondary',
        };
    }
}
