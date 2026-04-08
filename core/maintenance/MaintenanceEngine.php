<?php

declare(strict_types=1);

namespace Core\maintenance;

use Core\auth\AdminAuthenticator;
use Core\database\ConnectionManager;

final class MaintenanceEngine
{
    public function isEnabled(): bool
    {
        $state = $this->state();
        return (bool) ($state['enabled'] ?? false);
    }

    public function state(): array
    {
        $defaults = [
            'enabled' => false,
            'level' => 1,
            'reason' => '',
            'message' => 'Maintenance en cours',
            'started_at' => '',
            'enabled_by' => '',
            'allow_admin' => true,
            'allowed_ips' => [],
            'allowed_admin_ids' => [],
        ];

        try {
            $pdo = (new ConnectionManager())->connection();
            $table = (string) config('database.prefixes.core', 'core_') . 'settings';
            $stmt = $pdo->prepare('SELECT setting_key, setting_value FROM ' . $table . ' WHERE category = :category');
            $stmt->execute(['category' => 'maintenance']);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            if (!is_array($rows)) {
                return $defaults;
            }

            $state = $defaults;
            foreach ($rows as $row) {
                $key = (string) ($row['setting_key'] ?? '');
                $value = (string) ($row['setting_value'] ?? '');
                if ($key === '') {
                    continue;
                }
                $state[$key] = $value;
            }

            $state['enabled'] = in_array(strtolower((string) ($state['enabled'] ?? '0')), ['1', 'true', 'yes', 'on'], true);
            $state['allow_admin'] = in_array(strtolower((string) ($state['allow_admin'] ?? '1')), ['1', 'true', 'yes', 'on'], true);
            $state['level'] = max(1, min(3, (int) ($state['level'] ?? 1)));
            $allowedIpsRaw = $state['allowed_ips'] ?? '';
            if (is_array($allowedIpsRaw)) {
                $state['allowed_ips'] = array_values(array_unique(array_map(static fn ($v): string => trim((string) $v), $allowedIpsRaw)));
            } else {
                $state['allowed_ips'] = $this->parseList((string) $allowedIpsRaw);
            }

            $allowedAdminRaw = $state['allowed_admin_ids'] ?? '';
            if (is_array($allowedAdminRaw)) {
                $state['allowed_admin_ids'] = array_values(array_unique(array_map('intval', $allowedAdminRaw)));
            } else {
                $state['allowed_admin_ids'] = array_map('intval', $this->parseList((string) $allowedAdminRaw));
            }

            return $state;
        } catch (\Throwable) {
            return $defaults;
        }
    }

    public function allowsCurrentRequest(string $area): bool
    {
        $state = $this->state();
        if (!((bool) ($state['enabled'] ?? false))) {
            return true;
        }

        $clientIp = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
        if ($clientIp !== '' && in_array($clientIp, (array) ($state['allowed_ips'] ?? []), true)) {
            return true;
        }

        if ($area === 'front') {
            return false;
        }

        if ($area === 'admin') {
            if ($this->isSuperAdmin()) {
                return true;
            }

            $userId = $this->currentAdminId();
            if ($userId !== null && in_array($userId, (array) ($state['allowed_admin_ids'] ?? []), true)) {
                return true;
            }

            return (bool) ($state['allow_admin'] ?? false);
        }

        return false;
    }

    private function currentAdminId(): ?int
    {
        try {
            $auth = new AdminAuthenticator((new ConnectionManager())->connection());
            $user = $auth->currentUser();
            if (!is_array($user) || !isset($user['id'])) {
                return null;
            }
            return (int) $user['id'];
        } catch (\Throwable) {
            return null;
        }
    }

    private function isSuperAdmin(): bool
    {
        try {
            $auth = new AdminAuthenticator((new ConnectionManager())->connection());
            $user = $auth->currentUser();
            return is_array($user) && (string) ($user['role_slug'] ?? '') === 'super-admin';
        } catch (\Throwable) {
            return false;
        }
    }

    /** @return array<int, string> */
    private function parseList(string $raw): array
    {
        $normalized = str_replace(["\r\n", "\n", ';'], ',', $raw);
        $parts = array_map(static fn (string $v): string => trim($v), explode(',', $normalized));
        $parts = array_values(array_filter($parts, static fn (string $v): bool => $v !== ''));
        return array_values(array_unique($parts));
    }
}
