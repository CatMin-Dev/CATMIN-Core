<?php

declare(strict_types=1);

namespace Core\auth;

use Core\config\Config;
use PDO;

final class LockoutManager
{
    public function __construct(private readonly PDO $pdo) {}

    public function isLocked(string $identifier, string $ipAddress): bool
    {
        return $this->lockedUntil($identifier, $ipAddress) > time();
    }

    public function lockedUntil(string $identifier, string $ipAddress): int
    {
        $table = (string) Config::get('database.prefixes.admin', 'admin_') . 'login_attempts';

        $windowMinutes = (int) Config::get('security.lockout_window_minutes', 30);
        $threshold = date('Y-m-d H:i:s', time() - ($windowMinutes * 60));

        $stmt = $this->pdo->prepare(
            'SELECT attempted_at FROM ' . $table . ' WHERE success = 0 AND (identifier = :identifier OR ip_address = :ip_address) AND attempted_at >= :threshold ORDER BY attempted_at DESC LIMIT 20'
        );
        $stmt->execute([
            'identifier' => $identifier,
            'ip_address' => $ipAddress,
            'threshold' => $threshold,
        ]);

        $attempts = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $count = is_array($attempts) ? count($attempts) : 0;

        if ($count < 5) {
            return 0;
        }

        $seconds = $count >= 10 ? 1800 : ($count >= 7 ? 600 : 300);
        $latest = $attempts[0] ?? null;

        if (!is_string($latest)) {
            return 0;
        }

        $timestamp = strtotime($latest);
        if ($timestamp === false) {
            return 0;
        }

        return $timestamp + $seconds;
    }

    public function recordAttempt(string $identifier, string $ipAddress, bool $success): void
    {
        $table = (string) Config::get('database.prefixes.admin', 'admin_') . 'login_attempts';
        $stmt = $this->pdo->prepare(
            'INSERT INTO ' . $table . ' (identifier, ip_address, success, attempted_at) VALUES (:identifier, :ip_address, :success, CURRENT_TIMESTAMP)'
        );
        $stmt->execute([
            'identifier' => substr($identifier, 0, 191),
            'ip_address' => substr($ipAddress, 0, 64),
            'success' => $success ? 1 : 0,
        ]);
    }
}
