<?php

namespace App\Services;

use App\Models\AnalyticsEvent;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;

class AnalyticsService
{
    /**
     * @param array<string, mixed> $context
     * @param array<string, mixed> $metadata
     */
    public function track(
        string $eventName,
        string $domain,
        string $action,
        string $status = 'success',
        array $context = [],
        array $metadata = []
    ): void {
        if (!$this->isEnabled() || !$this->hasTable()) {
            return;
        }

        if ($this->eventNotTracked($eventName, $domain)) {
            return;
        }

        [$actorType, $actorId, $role] = $this->resolveActorContext();

        AnalyticsEvent::query()->create([
            'event_name' => $eventName,
            'domain' => $domain,
            'action' => $action,
            'status' => in_array($status, ['success', 'failed', 'warning'], true) ? $status : 'success',
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'role' => $role,
            'route_name' => request()?->route()?->getName(),
            'path' => $this->sanitizePath((string) request()?->path()),
            'context' => $this->sanitizePayload($context),
            'metadata' => $this->sanitizePayload($metadata),
            'occurred_at' => now(),
        ]);
    }

    public function isEnabled(): bool
    {
        return (bool) SettingService::get('analytics.enabled', config('catmin.analytics.enabled', false));
    }

    public function retentionDays(): int
    {
        $default = (int) config('catmin.analytics.retention_days', 30);
        $value = (int) SettingService::get('analytics.retention_days', $default);

        return max(7, min(365, $value));
    }

    /**
     * @return array<string, mixed>
     */
    public function dashboard(int $days = 7): array
    {
        if (!$this->hasTable()) {
            return [
                'enabled' => $this->isEnabled(),
                'days' => $days,
                'totals' => ['events' => 0, 'failed' => 0],
                'top_modules' => [],
                'top_actions' => [],
                'frictions' => [],
                'timeline' => [],
            ];
        }

        $days = max(1, min(30, $days));
        $from = now()->subDays($days);

        $base = AnalyticsEvent::query()->where('occurred_at', '>=', $from);

        $totalEvents = (clone $base)->count();
        $failedEvents = (clone $base)->where('status', 'failed')->count();

        $topModules = (clone $base)
            ->selectRaw('domain, count(*) as total')
            ->groupBy('domain')
            ->orderByDesc('total')
            ->limit(8)
            ->get()
            ->map(fn ($row) => ['domain' => (string) $row->domain, 'total' => (int) $row->total])
            ->all();

        $topActions = (clone $base)
            ->selectRaw('event_name, count(*) as total')
            ->groupBy('event_name')
            ->orderByDesc('total')
            ->limit(12)
            ->get()
            ->map(fn ($row) => ['event' => (string) $row->event_name, 'total' => (int) $row->total])
            ->all();

        $frictions = (clone $base)
            ->whereIn('status', ['failed', 'warning'])
            ->selectRaw('event_name, status, count(*) as total')
            ->groupBy('event_name', 'status')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'event' => (string) $row->event_name,
                'status' => (string) $row->status,
                'total' => (int) $row->total,
            ])
            ->all();

        $timeline = (clone $base)
            ->selectRaw('DATE(occurred_at) as day, count(*) as total')
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->map(fn ($row) => ['day' => (string) $row->day, 'total' => (int) $row->total])
            ->all();

        return [
            'enabled' => $this->isEnabled(),
            'days' => $days,
            'totals' => [
                'events' => $totalEvents,
                'failed' => $failedEvents,
                'success_rate' => $totalEvents > 0 ? round((($totalEvents - $failedEvents) / $totalEvents) * 100, 1) : 100.0,
            ],
            'top_modules' => $topModules,
            'top_actions' => $topActions,
            'frictions' => $frictions,
            'timeline' => $timeline,
        ];
    }

    public function prune(): int
    {
        if (!$this->hasTable()) {
            return 0;
        }

        return AnalyticsEvent::query()
            ->where('occurred_at', '<=', now()->subDays($this->retentionDays()))
            ->delete();
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function sanitizePayload(array $payload): array
    {
        $forbiddenKeys = [
            'password',
            'token',
            'secret',
            'cookie',
            'authorization',
            'content',
            'body',
            'email',
            'phone',
        ];

        $clean = [];
        foreach ($payload as $key => $value) {
            $keyString = strtolower((string) $key);
            if (in_array($keyString, $forbiddenKeys, true)) {
                continue;
            }

            if (is_scalar($value) || $value === null) {
                $valueString = (string) $value;
                if (strlen($valueString) > 180) {
                    $valueString = substr($valueString, 0, 180) . '...';
                }
                $clean[$key] = $valueString;
                continue;
            }

            if (is_array($value)) {
                $clean[$key] = Arr::take($value, 8);
            }
        }

        return $clean;
    }

    private function sanitizePath(string $path): string
    {
        if ($path === '') {
            return '';
        }

        $segments = array_filter(explode('/', $path), fn ($part) => $part !== '');
        $sanitized = [];

        foreach ($segments as $segment) {
            if (preg_match('/^\d+$/', $segment) || preg_match('/^[a-f0-9]{8,}$/i', $segment)) {
                $sanitized[] = '{id}';
                continue;
            }

            $sanitized[] = $segment;
        }

        return implode('/', $sanitized);
    }

    /**
     * @return array{0: string|null, 1: int|null, 2: string|null}
     */
    private function resolveActorContext(): array
    {
        $anonymousMode = (bool) SettingService::get('analytics.anonymous_mode', config('catmin.analytics.anonymous_mode', true));
        $username = (string) session('catmin_admin_username', '');

        $role = null;
        $roles = (array) session('catmin_rbac_roles', []);
        if (!empty($roles)) {
            $role = (string) $roles[0];
        }

        if ($anonymousMode) {
            return ['admin', null, $role];
        }

        if ($username === '') {
            return ['admin', null, $role];
        }

        $hash = crc32(strtolower($username));

        return ['admin', $hash, $role];
    }

    private function eventNotTracked(string $eventName, string $domain): bool
    {
        $tracked = SettingService::get('analytics.modules_tracked', config('catmin.analytics.modules_tracked', ['admin', 'module', 'content', 'ops', 'docs']));
        if (!is_array($tracked) || $tracked === []) {
            return false;
        }

        if (in_array('*', $tracked, true)) {
            return false;
        }

        return !in_array($domain, array_map('strval', $tracked), true);
    }

    private function hasTable(): bool
    {
        try {
            return Schema::hasTable('analytics_events');
        } catch (\Throwable) {
            return false;
        }
    }
}
