<?php

declare(strict_types=1);

return [
    'title' => 'Security',
    'validate' => static function (array $input, \Install\InstallerContext|null $context = null): array {
        $mode = (string) ($input['admin_path_mode'] ?? 'manual');
        if (!in_array($mode, ['auto', 'manual'], true)) {
            $mode = 'manual';
        }

        $rawAdminPath = $mode === 'auto'
            ? (string) ($input['admin_path_auto'] ?? '')
            : (string) ($input['admin_path'] ?? 'admin');
        $adminPath = trim($rawAdminPath, '/');
        $adminPath = preg_replace('/[^a-zA-Z0-9\-\_\/]/', '', $adminPath) ?? '';

        if ($adminPath === '') {
            $adminPath = $mode === 'auto' ? 'admin-' . substr(bin2hex(random_bytes(4)), 0, 8) : 'admin';
        }

        $whitelistEnabled = ($input['ip_whitelist_enabled'] ?? '') === '1';
        $rawWhitelist = (string) ($input['ip_whitelist'] ?? '');
        $tokens = preg_split('/[\s,]+/', $rawWhitelist) ?: [];
        $whitelist = [];
        foreach ($tokens as $token) {
            $ip = trim((string) $token);
            if ($ip === '') {
                continue;
            }
            if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
                continue;
            }
            $whitelist[] = $ip;
        }
        $whitelist = array_values(array_unique($whitelist));

        $detected = '';
        $forwarded = (string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? '');
        if ($forwarded !== '') {
            $parts = preg_split('/\s*,\s*/', $forwarded) ?: [];
            foreach ($parts as $candidate) {
                if (filter_var($candidate, FILTER_VALIDATE_IP) !== false) {
                    $detected = $candidate;
                    break;
                }
            }
        }
        if ($detected === '') {
            $remote = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
            if (filter_var($remote, FILTER_VALIDATE_IP) !== false) {
                $detected = $remote;
            }
        }

        if ($whitelistEnabled && $detected !== '' && !in_array($detected, $whitelist, true)) {
            array_unshift($whitelist, $detected);
        }

        return ['ok' => true, 'data' => [
            'admin_path_mode' => $mode,
            'admin_path' => $adminPath,
            'ip_whitelist_enabled' => $whitelistEnabled,
            'ip_whitelist' => $whitelist,
        ]];
    },
];
