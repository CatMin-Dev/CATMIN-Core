<?php

declare(strict_types=1);

use Core\database\ConnectionManager;
use Core\database\SchemaBuilder;

return static function (SchemaBuilder $schema, array $prefixes): void {
    $core = (string) ($prefixes['core'] ?? 'core_');
    $table = $core . 'modules';

    $manager = new ConnectionManager();
    $pdo = $manager->connection();
    $driver = $manager->driver();

    $hasColumn = static function (string $column) use ($pdo, $driver, $table): bool {
        try {
            if ($driver === 'sqlite') {
                $columns = $pdo->query('PRAGMA table_info(' . $table . ')');
                if ($columns === false) {
                    return false;
                }
                foreach ($columns->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
                    if ((string) ($row['name'] ?? '') === $column) {
                        return true;
                    }
                }
                return false;
            }

            $stmt = $pdo->prepare(
                'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table_name AND COLUMN_NAME = :column_name'
            );
            $stmt->execute([
                'table_name' => $table,
                'column_name' => $column,
            ]);

            return (int) ($stmt->fetchColumn() ?: 0) > 0;
        } catch (Throwable) {
            return false;
        }
    };

    try {
        if (!$hasColumn('enabled')) {
            if ($driver === 'sqlite') {
                $pdo->exec('ALTER TABLE ' . $table . ' ADD COLUMN enabled INTEGER NOT NULL DEFAULT 0');
            } else {
                $pdo->exec('ALTER TABLE ' . $table . ' ADD COLUMN enabled TINYINT(1) NOT NULL DEFAULT 0');
            }
        }

        if (!$hasColumn('schema_version')) {
            if ($driver === 'sqlite') {
                $pdo->exec('ALTER TABLE ' . $table . ' ADD COLUMN schema_version TEXT NULL');
            } else {
                $pdo->exec('ALTER TABLE ' . $table . ' ADD COLUMN schema_version VARCHAR(64) NULL');
            }
        }

        if (!$hasColumn('db_state')) {
            if ($driver === 'sqlite') {
                $pdo->exec("ALTER TABLE " . $table . " ADD COLUMN db_state TEXT NOT NULL DEFAULT 'disabled'");
            } else {
                $pdo->exec("ALTER TABLE " . $table . " ADD COLUMN db_state VARCHAR(64) NOT NULL DEFAULT 'disabled'");
            }
        }

        if (!$hasColumn('last_migration')) {
            if ($driver === 'sqlite') {
                $pdo->exec('ALTER TABLE ' . $table . ' ADD COLUMN last_migration TEXT NULL');
            } else {
                $pdo->exec('ALTER TABLE ' . $table . ' ADD COLUMN last_migration VARCHAR(191) NULL');
            }
        }

        $pdo->exec("UPDATE " . $table . " SET enabled = CASE WHEN LOWER(COALESCE(status, 'inactive')) = 'active' THEN 1 ELSE 0 END");
        $pdo->exec("UPDATE " . $table . " SET db_state = CASE WHEN LOWER(COALESCE(status, 'inactive')) = 'active' THEN 'enabled' ELSE 'disabled' END WHERE db_state IS NULL OR TRIM(db_state) = ''");
    } catch (Throwable) {
        // Best effort migration to preserve backward compatibility.
    }
};
