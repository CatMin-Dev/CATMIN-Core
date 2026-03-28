<?php

namespace Modules\Logger\Services;

use Modules\Logger\Models\SystemLog;

class ErrorTrackingService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function topRecurring(int $hours = 72, int $limit = 20): array
    {
        $from = now()->subHours(max(1, $hours));

        return SystemLog::query()
            ->where('channel', 'application')
            ->where('event', 'exception.reported')
            ->where('created_at', '>=', $from)
            ->orderByDesc('id')
            ->limit(3000)
            ->get()
            ->groupBy(function (SystemLog $log): string {
                $fingerprint = (string) data_get((array) $log->context, 'fingerprint', '');

                return $fingerprint !== '' ? $fingerprint : ('msg:' . substr(hash('sha256', (string) $log->message), 0, 16));
            })
            ->map(function ($items, string $fingerprint): array {
                $first = $items->last();
                $last = $items->first();

                return [
                    'fingerprint' => $fingerprint,
                    'count' => $items->count(),
                    'message' => (string) ($last->message ?? ''),
                    'exception' => (string) data_get((array) ($last->context ?? []), 'exception', ''),
                    'first_seen' => optional($first?->created_at)->toIso8601String(),
                    'last_seen' => optional($last?->created_at)->toIso8601String(),
                ];
            })
            ->sortByDesc('count')
            ->take(max(1, $limit))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public static function summary(int $hours = 24): array
    {
        $from = now()->subHours(max(1, $hours));

        $errors = SystemLog::query()
            ->where('channel', 'application')
            ->where('event', 'exception.reported')
            ->where('created_at', '>=', $from)
            ->count();

        return [
            'window_hours' => max(1, $hours),
            'errors' => $errors,
            'top' => self::topRecurring($hours, 10),
        ];
    }
}
