<?php

declare(strict_types=1);

final class CoreNotificationsPresenter
{
    public function badgeClass(string $type): string
    {
        return match (strtolower(trim($type))) {
            'success' => 'text-bg-success',
            'warning' => 'text-bg-warning',
            'danger' => 'text-bg-danger',
            'security' => 'text-bg-danger',
            'system' => 'text-bg-info',
            'module' => 'text-bg-secondary',
            default => 'text-bg-primary',
        };
    }

    public function label(string $type): string
    {
        return match (strtolower(trim($type))) {
            'success' => 'SUCCESS',
            'warning' => 'WARNING',
            'danger' => 'DANGER',
            'security' => 'SECURITY',
            'system' => 'SYSTEM',
            'module' => 'MODULE',
            default => 'INFO',
        };
    }
}

