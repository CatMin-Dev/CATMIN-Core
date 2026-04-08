<?php

declare(strict_types=1);

require_once CATMIN_CORE . '/logs/Logger.php';

final class CoreModuleSignatureLogger
{
    public function log(string $slug, string $status, string $keyId = ''): void
    {
        Core\logs\Logger::info('Module signature check', [
            'slug' => $slug,
            'status' => $status,
            'key_id' => $keyId,
        ]);
    }
}

