<?php

declare(strict_types=1);

namespace Core\security;

use Core\database\ConnectionManager;
use PDO;

final class TrustedDeviceRepository
{
    private const TABLE = 'core_trusted_devices';

    private PDO $pdo;
    private bool $schemaEnsured = false;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? (new ConnectionManager())->connection();
        $this->ensureSchema();
    }

    public function trustDevice(int $userId, string $fingerprintHash, string $deviceLabel = '', ?string $ipLast = null, ?string $userAgentLast = null): bool
    {
        $existing = $this->findActiveDevice($userId, $fingerprintHash);
        $bind = [
            'user_id' => $userId,
            'fingerprint_hash' => $fingerprintHash,
            'device_label' => trim($deviceLabel),
            'ip_last' => $ipLast,
            'user_agent_last' => $userAgentLast,
        ];

        if (is_array($existing)) {
            $stmt = $this->pdo->prepare(
                'UPDATE ' . self::TABLE . ' '
                . 'SET device_label = :device_label, ip_last = :ip_last, user_agent_last = :user_agent_last, last_seen_at = CURRENT_TIMESTAMP, revoked_at = NULL, updated_at = CURRENT_TIMESTAMP '
                . 'WHERE user_id = :user_id AND fingerprint_hash = :fingerprint_hash'
            );
            return $stmt->execute($bind);
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO ' . self::TABLE . ' '
            . '(user_id, fingerprint_hash, device_label, issued_at, last_seen_at, revoked_at, ip_last, user_agent_last, created_at, updated_at) '
            . 'VALUES (:user_id, :fingerprint_hash, :device_label, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, NULL, :ip_last, :user_agent_last, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        );

        return $stmt->execute($bind);
    }

    public function findActiveDevice(int $userId, string $fingerprintHash): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM ' . self::TABLE . ' '
            . 'WHERE user_id = :user_id AND fingerprint_hash = :fingerprint_hash AND revoked_at IS NULL '
            . 'LIMIT 1'
        );
        $stmt->execute([
            'user_id' => $userId,
            'fingerprint_hash' => $fingerprintHash,
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return is_array($row) ? $row : null;
    }

    public function touchLastSeen(int $userId, string $fingerprintHash, ?string $ipLast = null, ?string $userAgentLast = null): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE ' . self::TABLE . ' '
            . 'SET last_seen_at = CURRENT_TIMESTAMP, ip_last = :ip_last, user_agent_last = :user_agent_last, updated_at = CURRENT_TIMESTAMP '
            . 'WHERE user_id = :user_id AND fingerprint_hash = :fingerprint_hash AND revoked_at IS NULL'
        );

        return $stmt->execute([
            'user_id' => $userId,
            'fingerprint_hash' => $fingerprintHash,
            'ip_last' => $ipLast,
            'user_agent_last' => $userAgentLast,
        ]);
    }

    public function revokeDevice(int $userId, string $fingerprintHash): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE ' . self::TABLE . ' '
            . 'SET revoked_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP '
            . 'WHERE user_id = :user_id AND fingerprint_hash = :fingerprint_hash AND revoked_at IS NULL'
        );

        return $stmt->execute([
            'user_id' => $userId,
            'fingerprint_hash' => $fingerprintHash,
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listActiveDevicesByUser(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM ' . self::TABLE . ' WHERE user_id = :user_id AND revoked_at IS NULL ORDER BY issued_at DESC, id DESC'
        );
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function ensureSchema(): void
    {
        if ($this->schemaEnsured) {
            return;
        }

        $driver = (string) $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            $this->pdo->exec(
                'CREATE TABLE IF NOT EXISTS ' . self::TABLE . ' ('
                . 'id INTEGER PRIMARY KEY AUTOINCREMENT, '
                . 'user_id INTEGER NOT NULL, '
                . 'fingerprint_hash VARCHAR(128) NOT NULL, '
                . 'device_label VARCHAR(190) NOT NULL DEFAULT "", '
                . 'issued_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, '
                . 'last_seen_at DATETIME NULL, '
                . 'revoked_at DATETIME NULL, '
                . 'ip_last VARCHAR(64) NULL, '
                . 'user_agent_last VARCHAR(255) NULL, '
                . 'created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, '
                . 'updated_at DATETIME NULL'
                . ')'
            );
            $this->pdo->exec('CREATE INDEX IF NOT EXISTS ix_core_trusted_devices_user ON ' . self::TABLE . '(user_id)');
            $this->pdo->exec('CREATE UNIQUE INDEX IF NOT EXISTS ux_core_trusted_devices_user_hash ON ' . self::TABLE . '(user_id, fingerprint_hash)');
            $this->pdo->exec('CREATE INDEX IF NOT EXISTS ix_core_trusted_devices_revoked ON ' . self::TABLE . '(revoked_at)');
        } else {
            $this->pdo->exec(
                'CREATE TABLE IF NOT EXISTS ' . self::TABLE . ' ('
                . 'id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, '
                . 'user_id BIGINT UNSIGNED NOT NULL, '
                . 'fingerprint_hash VARCHAR(128) NOT NULL, '
                . 'device_label VARCHAR(190) NOT NULL DEFAULT "", '
                . 'issued_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, '
                . 'last_seen_at DATETIME NULL, '
                . 'revoked_at DATETIME NULL, '
                . 'ip_last VARCHAR(64) NULL, '
                . 'user_agent_last VARCHAR(255) NULL, '
                . 'created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, '
                . 'updated_at DATETIME NULL, '
                . 'UNIQUE KEY ux_core_trusted_devices_user_hash (user_id, fingerprint_hash), '
                . 'KEY ix_core_trusted_devices_user (user_id), '
                . 'KEY ix_core_trusted_devices_revoked (revoked_at)'
                . ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
            );
        }

        $this->schemaEnsured = true;
    }
}