<?php

declare(strict_types=1);

namespace Core\database;

use Core\config\Config;
use PDO;
use RuntimeException;

final class ConnectionManager
{
    /** @var array<string, PDO> */
    private array $connections = [];

    public function __construct(private readonly DriverResolver $resolver = new DriverResolver()) {}

    public function connection(?string $name = null): PDO
    {
        $name = $name ?: (string) Config::get('database.default', 'sqlite');

        if (isset($this->connections[$name])) {
            return $this->connections[$name];
        }

        $config = Config::get('database.connections.' . $name);
        if (!is_array($config)) {
            throw new RuntimeException('Database connection not configured: ' . $name);
        }

        $driver = $this->resolver->normalize((string) ($config['driver'] ?? $name));

        if ($driver === 'sqlite') {
            $path = (string) ($config['database'] ?? '');
            if ($path === '') {
                throw new RuntimeException('SQLite database path is required.');
            }

            $dir = dirname($path);
            if (!is_dir($dir)) {
                mkdir($dir, 0775, true);
            }
            if (!is_file($path)) {
                touch($path);
            }
        }

        $dsn = $this->resolver->dsn($driver, $config);
        $username = (string) ($config['username'] ?? '');
        $password = (string) ($config['password'] ?? '');

        $pdo = new PDO($dsn, $username !== '' ? $username : null, $password !== '' ? $password : null, $this->resolver->pdoOptions());

        if ($driver === 'mysql' && isset($config['collation'])) {
            $pdo->exec("SET NAMES '" . (string) ($config['charset'] ?? 'utf8mb4') . "' COLLATE '" . (string) $config['collation'] . "'");
        }

        $this->connections[$name] = $pdo;

        return $pdo;
    }

    public function driver(?string $name = null): string
    {
        $name = $name ?: (string) Config::get('database.default', 'sqlite');
        $config = Config::get('database.connections.' . $name, []);
        if (!is_array($config)) {
            return 'sqlite';
        }

        return $this->resolver->normalize((string) ($config['driver'] ?? $name));
    }
}
