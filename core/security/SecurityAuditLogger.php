<?php

declare(strict_types=1);

namespace Core\security;

use Core\config\Config;
use Core\database\ConnectionManager;
use Throwable;

final class SecurityAuditLogger
{
    public function log(string $eventType, string $severity, array $payload = [], ?int $userId = null): void
    {
        try {
            $table = (string) Config::get('database.prefixes.admin', 'admin_') . 'security_events';
            $pdo = (new ConnectionManager())->connection();
            $stmt = $pdo->prepare(
                'INSERT INTO ' . $table . ' (user_id, event_type, severity, payload, ip_address, created_at) VALUES (:user_id, :event_type, :severity, :payload, :ip_address, CURRENT_TIMESTAMP)'
            );
            $stmt->execute([
                'user_id' => $userId,
                'event_type' => substr($eventType, 0, 120),
                'severity' => substr($severity, 0, 40),
                'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'ip_address' => substr((string) ($payload['ip'] ?? ''), 0, 64),
            ]);

            return;
        } catch (Throwable) {
            // Fallback to file log
        }

        $line = sprintf(
            "[%s] %s %s %s\n",
            date('c'),
            strtoupper($severity),
            $eventType,
            json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        $path = CATMIN_STORAGE . '/logs/security-audit.log';
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        file_put_contents($path, $line, FILE_APPEND);
    }
}
