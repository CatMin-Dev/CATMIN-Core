<?php

declare(strict_types=1);

final class CoreModuleRepositoryLogger
{
    public function info(string $message, array $context = []): void
    {
        \Core\logs\Logger::info($message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        \Core\logs\Logger::warning($message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        \Core\logs\Logger::error($message, $context);
    }
}
