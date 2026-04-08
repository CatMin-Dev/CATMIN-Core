<?php

declare(strict_types=1);

require_once CATMIN_CORE . '/logs/Logger.php';

final class CoreModuleIntegrityLogger
{
    public function log(string $slug, string $status, array $context = []): void
    {
        Core\logs\Logger::info('Module integrity check', [
            'slug' => $slug,
            'status' => $status,
            'context' => $context,
        ]);
    }
}

