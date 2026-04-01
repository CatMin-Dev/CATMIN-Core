<?php

namespace App\Services;

class Analytics
{
    /**
     * @param array<string, mixed> $context
     * @param array<string, mixed> $metadata
     */
    public static function track(
        string $eventName,
        string $domain,
        string $action,
        string $status = 'success',
        array $context = [],
        array $metadata = []
    ): void {
        app(AnalyticsService::class)->track($eventName, $domain, $action, $status, $context, $metadata);
    }
}
