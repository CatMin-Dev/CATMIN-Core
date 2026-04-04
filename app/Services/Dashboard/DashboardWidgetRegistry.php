<?php

namespace App\Services\Dashboard;

class DashboardWidgetRegistry
{
    /**
     * @var array<int, array{priority:int, provider:callable}>
     */
    protected static array $providers = [];

    public static function register(callable $provider, int $priority = 100): void
    {
        self::$providers[] = [
            'priority' => $priority,
            'provider' => $provider,
        ];
    }

    /**
     * @param array<string, mixed> $context
     * @return array<int, array<string, mixed>>
     */
    public static function collect(array $context = []): array
    {
        $widgets = [];

        foreach (self::providers() as $entry) {
            $payload = ($entry['provider'])($context);

            if ($payload === null) {
                continue;
            }

            $rows = self::normalizeResult($payload);

            foreach ($rows as $widget) {
                $widgets[] = $widget;
            }
        }

        return collect($widgets)
            ->sortBy([
                ['order', 'asc'],
                ['title', 'asc'],
            ])
            ->values()
            ->all();
    }

    public static function flush(): void
    {
        self::$providers = [];
    }

    /**
     * @return array<int, array{priority:int, provider:callable}>
     */
    protected static function providers(): array
    {
        return collect(self::$providers)
            ->sortBy('priority')
            ->values()
            ->all();
    }

    /**
     * @param mixed $payload
     * @return array<int, array<string, mixed>>
     */
    protected static function normalizeResult(mixed $payload): array
    {
        if (!is_array($payload)) {
            return [];
        }

        $isList = array_is_list($payload);

        if (!$isList) {
            return [self::normalizeWidget($payload)];
        }

        return collect($payload)
            ->filter(fn ($row) => is_array($row))
            ->map(fn (array $row) => self::normalizeWidget($row))
            ->values()
            ->all();
    }

    /**
     * @param array<string, mixed> $widget
     * @return array<string, mixed>
     */
    protected static function normalizeWidget(array $widget): array
    {
        return [
            'id' => (string) ($widget['id'] ?? uniqid('widget_', true)),
            'title' => (string) ($widget['title'] ?? 'Widget'),
            'subtitle' => (string) ($widget['subtitle'] ?? ''),
            'type' => (string) ($widget['type'] ?? 'list'),
            'tone' => (string) ($widget['tone'] ?? 'secondary'),
            'order' => (int) ($widget['order'] ?? 100),
            'zone' => (string) ($widget['zone'] ?? 'secondary'),
            'priority' => (int) ($widget['priority'] ?? ($widget['order'] ?? 100)),
            'span' => (string) ($widget['span'] ?? 'half'),
            'items' => is_array($widget['items'] ?? null)
                ? collect($widget['items'])->filter(fn ($item) => is_array($item))->values()->all()
                : [],
            'empty' => (string) ($widget['empty'] ?? 'Aucune donnee.'),
            'action' => is_array($widget['action'] ?? null) ? $widget['action'] : null,
        ];
    }
}
