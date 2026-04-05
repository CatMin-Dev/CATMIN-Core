<?php

declare(strict_types=1);

namespace Core\database;

use Core\config\Config;
use PDO;
use RuntimeException;

final class SeederRunner
{
    public function __construct(private readonly ConnectionManager $connections = new ConnectionManager()) {}

    public function runBase(?string $connectionName = null): void
    {
        $pdo = $this->connections->connection($connectionName);

        $seedersPath = (string) Config::get('database.seeders_path', base_path('core/database/seeders'));
        $prefixes = Config::get('database.prefixes', []);

        if (!is_array($prefixes)) {
            throw new RuntimeException('Database prefixes configuration is invalid.');
        }

        foreach (glob(rtrim($seedersPath, '/') . '/*.php') ?: [] as $file) {
            $seeder = require $file;
            if (!is_callable($seeder)) {
                throw new RuntimeException('Invalid seeder file: ' . $file);
            }
            $seeder($pdo, $prefixes);
        }
    }
}
