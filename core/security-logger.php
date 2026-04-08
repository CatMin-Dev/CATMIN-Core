<?php

declare(strict_types=1);

use Core\security\SecurityAuditLogger;

require_once CATMIN_CORE . '/security/SecurityAuditLogger.php';

final class CoreSecurityLogger
{
    private SecurityAuditLogger $logger;

    public function __construct()
    {
        $this->logger = new SecurityAuditLogger();
    }

    public function log(string $eventType, string $severity, array $payload = [], ?int $userId = null): void
    {
        $this->logger->log($eventType, $severity, $payload, $userId);
    }
}

