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

        $defaultPorts = [
            'sqlite' => 0,
            'mysql' => 3306,
            'mariadb' => 3306,
            'pgsql' => 5432,
            'sqlsrv' => 1433,
        ];
        $defaultPort = (int) ($defaultPorts[$driver] ?? 0);
        $rawPort = trim((string) ($input['port'] ?? ''));

        $payload = [
            'driver' => $driver,
            'sqlite_path' => trim((string) ($input['sqlite_path'] ?? 'db/database.sqlite')),
            'host' => (string) ($input['host'] ?? '127.0.0.1'),
            'port' => $rawPort === '' ? $defaultPort : (int) $rawPort,
            'database' => trim((string) ($input['database'] ?? 'catmin')),
            'username' => (string) ($input['username'] ?? ''),
            'password' => (string) ($input['password'] ?? ''),
        ];

        if ($driver === 'sqlite' && $payload['sqlite_path'] === '') {
            return ['ok' => false, 'message' => 'SQLite path required.', 'data' => []];
        }

        if ($driver !== 'sqlite' && $payload['database'] === '') {
            return ['ok' => false, 'message' => 'Database name required.', 'data' => []];
        }

        return ['ok' => true, 'data' => $payload];
    },
];
