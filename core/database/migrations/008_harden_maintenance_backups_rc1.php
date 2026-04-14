<?php

declare(strict_types=1);

use Core\database\ConnectionManager;
use Core\database\SchemaBuilder;

return static function (SchemaBuilder $schema, array $prefixes): void {
    $core = (string) ($prefixes['core'] ?? 'core_');
    $backupsTable = $core . 'backups';
    $auditTable = $core . 'maintenance_audit';

    $manager = new ConnectionManager();
    $pdo = $manager->connection();
    $driver = $manager->driver();

    $hasColumn = static function (string $table, string $column) use ($pdo, $driver): bool {
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

    $addColumn = static function (string $column, string $sqliteSql, string $mysqlSql) use ($pdo, $driver, $backupsTable, $hasColumn): void {
        if ($hasColumn($backupsTable, $column)) {
            return;
        }
        if ($driver === 'sqlite') {
            $pdo->exec('ALTER TABLE ' . $backupsTable . ' ADD COLUMN ' . $sqliteSql);
            return;
        }
        $pdo->exec('ALTER TABLE ' . $backupsTable . ' ADD COLUMN ' . $mysqlSql);
    };

    try {
        $addColumn('backup_format_version', "backup_format_version TEXT NOT NULL DEFAULT '0.1.0-RC.1'", "backup_format_version VARCHAR(64) NOT NULL DEFAULT '0.1.0-RC.1'");
        $addColumn('core_version', "core_version TEXT NOT NULL DEFAULT ''", "core_version VARCHAR(64) NOT NULL DEFAULT ''");
        $addColumn('origin', "origin TEXT NOT NULL DEFAULT 'manual'", "origin VARCHAR(64) NOT NULL DEFAULT 'manual'");
        $addColumn('manifest', 'manifest TEXT NULL', 'manifest JSON NULL');
        $addColumn('integrity_status', "integrity_status TEXT NOT NULL DEFAULT 'unknown'", "integrity_status VARCHAR(40) NOT NULL DEFAULT 'unknown'");
        $addColumn('is_orphan', 'is_orphan INTEGER NOT NULL DEFAULT 0', 'is_orphan TINYINT(1) NOT NULL DEFAULT 0');
        $addColumn('created_by_user_id', 'created_by_user_id INTEGER NULL', 'created_by_user_id BIGINT NULL');
        $addColumn('created_by_username', "created_by_username TEXT NOT NULL DEFAULT ''", "created_by_username VARCHAR(120) NOT NULL DEFAULT ''");
        $addColumn('last_error', 'last_error TEXT NULL', 'last_error TEXT NULL');
        $addColumn('lock_token', 'lock_token TEXT NULL', 'lock_token VARCHAR(120) NULL');
        $addColumn('updated_at', 'updated_at DATETIME NULL', 'updated_at DATETIME NULL');

        if ($driver !== 'sqlite') {
            try {
                $pdo->exec('CREATE INDEX ix_' . $backupsTable . '_type_created ON ' . $backupsTable . ' (backup_type, created_at)');
            } catch (Throwable) {
            }
            try {
                $pdo->exec('CREATE INDEX ix_' . $backupsTable . '_integrity ON ' . $backupsTable . ' (integrity_status, is_orphan)');
            } catch (Throwable) {
            }
        }

        $schema->create($auditTable, [
            ['name' => 'id', 'type' => 'bigint', 'primary' => true, 'auto_increment' => true],
            ['name' => 'action', 'type' => 'string', 'length' => 120],
            ['name' => 'result', 'type' => 'string', 'length' => 40],
            ['name' => 'message', 'type' => 'string', 'length' => 255],
            ['name' => 'actor_user_id', 'type' => 'bigint', 'nullable' => true],
            ['name' => 'actor_username', 'type' => 'string', 'length' => 120, 'nullable' => true],
            ['name' => 'ip_address', 'type' => 'string', 'length' => 64, 'nullable' => true],
            ['name' => 'context', 'type' => 'json', 'nullable' => true],
            ['name' => 'created_at', 'type' => 'datetime', 'default' => 'CURRENT_TIMESTAMP'],
        ], [
            ['name' => 'ix_' . $auditTable . '_action', 'columns' => ['action']],
            ['name' => 'ix_' . $auditTable . '_created_at', 'columns' => ['created_at']],
        ]);

        $pdo->exec("UPDATE " . $backupsTable . " SET backup_format_version = '0.1.0-RC.1' WHERE backup_format_version IS NULL OR TRIM(backup_format_version) = ''");
        $pdo->exec("UPDATE " . $backupsTable . " SET origin = CASE WHEN backup_type = 'auto' THEN 'automatic' WHEN backup_type = 'restore' THEN 'restore' ELSE 'manual' END WHERE origin IS NULL OR TRIM(origin) = ''");
        $pdo->exec("UPDATE " . $backupsTable . " SET integrity_status = CASE WHEN file_path IS NULL OR TRIM(file_path) = '' THEN 'unknown' ELSE 'ok' END WHERE integrity_status IS NULL OR TRIM(integrity_status) = ''");
    } catch (Throwable) {
        // Best effort migration; keep compatibility.
    }
};
