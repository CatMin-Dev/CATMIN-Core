<?php

declare(strict_types=1);

return [
    'default' => 'sqlite',
    'schema_version' => '0.1.0-dev.2',
    'migrations_path' => base_path('core/database/migrations'),
    'seeders_path' => base_path('core/database/seeders'),
    'connections' => [
        'sqlite' => [
            'driver' => 'sqlite',
            'database' => base_path('storage/database.sqlite'),
        ],
        'mysql' => [
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'port' => 3306,
            'database' => 'catmin',
            'username' => 'root',
            'password' => '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ],
        'mariadb' => [
            'driver' => 'mariadb',
            'host' => '127.0.0.1',
            'port' => 3306,
            'database' => 'catmin',
            'username' => 'root',
            'password' => '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ],
        'pgsql' => [
            'driver' => 'pgsql',
            'host' => '127.0.0.1',
            'port' => 5432,
            'database' => 'catmin',
            'username' => 'postgres',
            'password' => '',
        ],
        'sqlsrv' => [
            'driver' => 'sqlsrv',
            'host' => '127.0.0.1',
            'port' => 1433,
            'database' => 'catmin',
            'username' => 'sa',
            'password' => '',
        ],
    ],
    'prefixes' => [
        'admin' => 'admin_',
        'core' => 'core_',
        'front' => 'front_',
        'module' => 'module_{slug}_',
    ],
];
