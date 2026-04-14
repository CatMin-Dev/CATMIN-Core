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
            'level' => 0,
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
            $state['level'] = max(0, min(4, (int) ($state['level'] ?? 0)));
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
        $enabled = (bool) ($state['enabled'] ?? false);
        $level = max(0, min(4, (int) ($state['level'] ?? 0)));

        if (!$enabled || $level === 0) {
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
            $isSuperAdmin = $this->isSuperAdmin();
            $userId = $this->currentAdminId();
            $isWhitelistedAdmin = $userId !== null && in_array($userId, (array) ($state['allowed_admin_ids'] ?? []), true);

            if ($level === 4) {
                return $isSuperAdmin && ($isWhitelistedAdmin || in_array($clientIp, (array) ($state['allowed_ips'] ?? []), true));
            }

            if ($isSuperAdmin) {
                return true;
            }

            if ($level >= 3) {
                return $isWhitelistedAdmin;
            }

            if ($isWhitelistedAdmin) {
                return true;
            }

            return (bool) ($state['allow_admin'] ?? false);
        }

        return false;
    }

    public function policyForLevel(int $level): array
    {
        $level = max(0, min(4, $level));
        return match ($level) {
            0 => [
                'access' => 'Normal',
                'blocked' => 'Aucun blocage',
                'allowed' => 'Toutes operations standards',
                'usage' => 'Exploitation normale',
            ],
            1 => [
                'access' => 'Admins + whitelists',
                'blocked' => 'Front public',
                'allowed' => 'Operations non destructives',
                'usage' => 'Intervention legere',
            ],
            2 => [
                'access' => 'Admins limites + whitelists',
                'blocked' => 'Front + operations non conformes',
                'allowed' => 'Operations techniques controlees',
                'usage' => 'Maintenance technique',
            ],
            3 => [
                'access' => 'Superadmin + admins whitelistes',
                'blocked' => 'Acces admin general',
                'allowed' => 'Operations critiques',
                'usage' => 'Maintenance lourde',
            ],
            default => [
                'access' => 'Superadmin whiteliste uniquement',
                'blocked' => 'Tout hors exception',
                'allowed' => 'Urgence/restore critique',
                'usage' => 'Verrouillage total',
            ],
        };
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
