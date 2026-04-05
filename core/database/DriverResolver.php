<?php

declare(strict_types=1);

namespace Core\database;

use InvalidArgumentException;

final class DriverResolver
{
    public function normalize(string $driver): string
    {
        $normalized = strtolower(trim($driver));

        return match ($normalized) {
            'mysql', 'mariadb' => 'mysql',
            'sqlite' => 'sqlite',
            'pgsql', 'postgresql' => 'pgsql',
            'sqlsrv', 'mssql' => 'sqlsrv',
            default => throw new InvalidArgumentException('Unsupported database driver: ' . $driver),
        };
    }

    public function dsn(string $driver, array $connection): string
    {
        $driver = $this->normalize($driver);

        return match ($driver) {
            'sqlite' => 'sqlite:' . (string) ($connection['database'] ?? ''),
            'mysql' => sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                (string) ($connection['host'] ?? '127.0.0.1'),
                (string) ($connection['port'] ?? 3306),
                (string) ($connection['database'] ?? ''),
                (string) ($connection['charset'] ?? 'utf8mb4')
            ),
            'pgsql' => sprintf(
                'pgsql:host=%s;port=%s;dbname=%s',
                (string) ($connection['host'] ?? '127.0.0.1'),
                (string) ($connection['port'] ?? 5432),
                (string) ($connection['database'] ?? '')
            ),
            'sqlsrv' => sprintf(
                'sqlsrv:Server=%s,%s;Database=%s',
                (string) ($connection['host'] ?? '127.0.0.1'),
                (string) ($connection['port'] ?? 1433),
                (string) ($connection['database'] ?? '')
            ),
        };
    }

    public function pdoOptions(): array
    {
        return [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => false,
        ];
    }
}
