<?php

declare(strict_types=1);

return [
    'title' => 'Identity',
    'validate' => static function (array $input, \Install\InstallerContext|null $context = null): array {
        $appName = trim((string) ($input['app_name'] ?? 'CATMIN'));
        $appUrl = trim((string) ($input['app_url'] ?? '/'));
        $operator = trim((string) ($input['operator_name'] ?? ''));

        if ($appName === '') {
            return ['ok' => false, 'message' => 'App name required.', 'data' => []];
        }

        return ['ok' => true, 'data' => [
            'app_name' => $appName,
            'app_url' => $appUrl !== '' ? $appUrl : '/',
            'operator_name' => $operator,
        ]];
    },
];
