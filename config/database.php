<?php

declare(strict_types=1);

$defaultDriver = (string) env('CATMIN_DB_DRIVER', 'sqlite');

return [
    'default' => $defaultDriver,
    'schema_version' => '0.1.0-dev.2',
    'migrations_path' => base_path('core/database/migrations'),
    'seeders_path' => base_path('core/database/seeders'),
    'connections' => [
        'sqlite' => [
            'driver' => 'sqlite',
            'database' => (string) env('CATMIN_DB_SQLITE_PATH', base_path('storage/database.sqlite')),
        ],
        'mysql' => [
            'driver' => 'mysql',
            'host' => (string) env('CATMIN_DB_HOST', '127.0.0.1'),
            'port' => (int) env('CATMIN_DB_PORT', 3306),
            'database' => (string) env('CATMIN_DB_NAME', 'catmin'),
            'username' => (string) env('CATMIN_DB_USER', 'root'),
            'password' => (string) env('CATMIN_DB_PASS', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ],
        'mariadb' => [
            'driver' => 'mariadb',
            'host' => (string) env('CATMIN_DB_HOST', '127.0.0.1'),
            'port' => (int) env('CATMIN_DB_PORT', 3306),
            'database' => (string) env('CATMIN_DB_NAME', 'catmin'),
            'username' => (string) env('CATMIN_DB_USER', 'root'),
            'password' => (string) env('CATMIN_DB_PASS', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ],
        'pgsql' => [
            'driver' => 'pgsql',
            'host' => (string) env('CATMIN_DB_HOST', '127.0.0.1'),
            'port' => (int) env('CATMIN_DB_PORT', 5432),
            'database' => (string) env('CATMIN_DB_NAME', 'catmin'),
            'username' => (string) env('CATMIN_DB_USER', 'postgres'),
            'password' => (string) env('CATMIN_DB_PASS', ''),
        ],
        'sqlsrv' => [
            'driver' => 'sqlsrv',
            'host' => (string) env('CATMIN_DB_HOST', '127.0.0.1'),
            'port' => (int) env('CATMIN_DB_PORT', 1433),
            'database' => (string) env('CATMIN_DB_NAME', 'catmin'),
            'username' => (string) env('CATMIN_DB_USER', 'sa'),
            'password' => (string) env('CATMIN_DB_PASS', ''),
        ],
    ],
    'prefixes' => [
        'admin' => 'admin_',
        'core' => 'core_',
        'front' => 'front_',
        'module' => 'module_{slug}_',
    ],
];
