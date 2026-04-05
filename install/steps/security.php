<?php

declare(strict_types=1);

return [
    'title' => 'Security',
    'validate' => static function (array $input, \Install\InstallerContext|null $context = null): array {
        $adminPath = trim((string) ($input['admin_path'] ?? 'admin'), '/');
        $adminPath = preg_replace('/[^a-zA-Z0-9\-\_\/]/', '', $adminPath) ?? 'admin';

        if ($adminPath === '') {
            $adminPath = 'admin';
        }

        return ['ok' => true, 'data' => [
            'admin_path' => $adminPath,
            'ip_whitelist_enabled' => ($input['ip_whitelist_enabled'] ?? '') === '1',
        ]];
    },
];
