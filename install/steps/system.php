<?php

declare(strict_types=1);

return [
    'title' => 'System',
    'validate' => static function (array $input, \Install\InstallerContext|null $context = null): array {
        $timezone = (string) ($input['timezone'] ?? 'UTC');
        $validTz = in_array($timezone, timezone_identifiers_list(), true);

        return ['ok' => true, 'data' => [
            'timezone' => $validTz ? $timezone : 'UTC',
            'consent_tracking' => ($input['consent_tracking'] ?? '') === '1',
        ]];
    },
];
