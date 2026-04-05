<?php

declare(strict_types=1);

return [
    'title' => 'Database',
    'validate' => static function (array $input, \Install\InstallerContext|null $context = null): array {
        $driver = (string) ($input['driver'] ?? 'sqlite');
        $allowed = ['sqlite', 'mysql', 'mariadb', 'pgsql', 'sqlsrv'];

        if (!in_array($driver, $allowed, true)) {
            return ['ok' => false, 'message' => 'Invalid database driver.', 'data' => []];
        }

        $payload = [
            'driver' => $driver,
            'sqlite_path' => (string) ($input['sqlite_path'] ?? base_path('storage/database.sqlite')),
            'host' => (string) ($input['host'] ?? '127.0.0.1'),
            'port' => (int) ($input['port'] ?? 0),
            'database' => (string) ($input['database'] ?? ''),
            'username' => (string) ($input['username'] ?? ''),
            'password' => (string) ($input['password'] ?? ''),
        ];

        if ($driver !== 'sqlite' && $payload['database'] === '') {
            return ['ok' => false, 'message' => 'Database name required.', 'data' => []];
        }

        return ['ok' => true, 'data' => $payload];
    },
];
