<?php

declare(strict_types=1);

return [
    'title' => 'Precheck',
    'validate' => static function (array $input, \Install\InstallerContext|null $context = null): array {
        $checks = [
            'php_version' => version_compare(PHP_VERSION, '8.3.0', '>='),
            'storage_writable' => is_writable(CATMIN_STORAGE) || is_writable(dirname(CATMIN_STORAGE)),
            'pdo_enabled' => extension_loaded('pdo'),
        ];

        foreach ($checks as $ok) {
            if (!$ok) {
                return ['ok' => false, 'message' => 'Precheck failed.', 'data' => ['checks' => $checks]];
            }
        }

        return ['ok' => true, 'data' => ['checks' => $checks]];
    },
];
