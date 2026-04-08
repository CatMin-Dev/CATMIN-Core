<?php

declare(strict_types=1);

use Core\config\Config;

require_once CATMIN_CORE . '/db-connection.php';

final class CoreDbVersionManager
{
    private CoreDbConnection $connection;

    public function __construct(?CoreDbConnection $connection = null)
    {
        $this->connection = $connection ?? new CoreDbConnection();
    }

    public function expectedSchemaVersion(): string
    {
        return (string) Config::get('database.schema_version', '0.1.0-dev.1');
    }

    public function currentSchemaVersion(?string $connectionName = null): string
    {
        $pdo = $this->connection->get($connectionName);
        $table = (string) Config::get('database.prefixes.core', 'core_') . 'db_versions';

        try {
            $stmt = $pdo->query('SELECT schema_version FROM ' . $table . ' ORDER BY id DESC LIMIT 1');
            $value = $stmt !== false ? $stmt->fetchColumn() : false;
            return is_string($value) && $value !== '' ? $value : '0.0.0-dev.0';
        } catch (Throwable) {
            return '0.0.0-dev.0';
        }
    }

    public function lastMigration(?string $connectionName = null): string
    {
        $pdo = $this->connection->get($connectionName);
        $table = (string) Config::get('database.prefixes.core', 'core_') . 'db_versions';
        try {
            $stmt = $pdo->query('SELECT migration FROM ' . $table . ' ORDER BY id DESC LIMIT 1');
            $value = $stmt !== false ? $stmt->fetchColumn() : false;
            return is_string($value) ? $value : '-';
        } catch (Throwable) {
            return '-';
        }
    }
}

