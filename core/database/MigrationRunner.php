<?php

declare(strict_types=1);

namespace Core\database;

use Core\config\Config;
use PDO;
use RuntimeException;

final class MigrationRunner
{
    public function __construct(private readonly ConnectionManager $connections = new ConnectionManager()) {}

    public function run(?string $connectionName = null): array
    {
        $pdo = $this->connections->connection($connectionName);
        $driver = $this->connections->driver($connectionName);
        $schema = new SchemaBuilder($pdo, $driver);

        $prefixes = Config::get('database.prefixes', []);
        if (!is_array($prefixes)) {
            throw new RuntimeException('Database prefixes configuration is invalid.');
        }

        $migrationsPath = (string) Config::get('database.migrations_path', base_path('core/database/migrations'));
        $schemaVersion = (string) Config::get('database.schema_version', '0.1.0-dev.1');

        $this->ensureDbVersionsTable($schema, $prefixes['core'] ?? 'core_');

        $applied = $this->appliedMigrations($pdo, $prefixes['core'] ?? 'core_');
        $batch = $this->nextBatch($pdo, $prefixes['core'] ?? 'core_');

        $executed = [];
        foreach (glob(rtrim($migrationsPath, '/') . '/*.php') ?: [] as $file) {
            $name = basename($file);
            if (in_array($name, $applied, true)) {
                continue;
            }

            $migration = require $file;
            if (!is_callable($migration)) {
                throw new RuntimeException('Invalid migration file: ' . $file);
            }

            $pdo->beginTransaction();
            try {
                $migration($schema, $prefixes);

                $table = ($prefixes['core'] ?? 'core_') . 'db_versions';
                $sql = 'INSERT INTO ' . $table . ' (migration, schema_version, batch, applied_at) VALUES (:migration, :schema_version, :batch, CURRENT_TIMESTAMP)';
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    'migration' => $name,
                    'schema_version' => $schemaVersion,
                    'batch' => $batch,
                ]);

                $pdo->commit();
                $executed[] = $name;
            } catch (\Throwable $exception) {
                $pdo->rollBack();
                throw $exception;
            }
        }

        return $executed;
    }

    private function ensureDbVersionsTable(SchemaBuilder $schema, string $corePrefix): void
    {
        $schema->create($corePrefix . 'db_versions', [
            ['name' => 'id', 'type' => 'bigint', 'primary' => true, 'auto_increment' => true],
            ['name' => 'migration', 'type' => 'string', 'length' => 191],
            ['name' => 'schema_version', 'type' => 'string', 'length' => 64],
            ['name' => 'batch', 'type' => 'integer', 'default' => 1],
            ['name' => 'applied_at', 'type' => 'datetime', 'default' => 'CURRENT_TIMESTAMP'],
        ], [
            ['name' => 'ux_core_db_versions_migration', 'columns' => ['migration'], 'unique' => true],
        ]);
    }

    private function appliedMigrations(PDO $pdo, string $corePrefix): array
    {
        $table = $corePrefix . 'db_versions';
        $stmt = $pdo->query('SELECT migration FROM ' . $table);
        $rows = $stmt !== false ? $stmt->fetchAll(PDO::FETCH_COLUMN) : [];

        return array_map('strval', is_array($rows) ? $rows : []);
    }

    private function nextBatch(PDO $pdo, string $corePrefix): int
    {
        $table = $corePrefix . 'db_versions';
        $stmt = $pdo->query('SELECT MAX(batch) FROM ' . $table);
        $current = $stmt !== false ? (int) $stmt->fetchColumn() : 0;

        return $current + 1;
    }
}
