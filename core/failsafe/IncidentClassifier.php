<?php

declare(strict_types=1);

namespace Core\failsafe;

final class IncidentClassifier
{
    public function classifyFromStatus(int $status): string
    {
        return match (true) {
            $status >= 500 => 'critical',
            $status >= 400 => 'warning',
            default => 'info',
        };
    }

    public function classifyFromThrowable(\Throwable $throwable): string
    {
        $message = strtolower($throwable->getMessage());
        if (str_contains($message, 'pdo') || str_contains($message, 'database') || str_contains($message, 'connection')) {
            return 'critical';
        }

        return 'error';
    }
}

