<?php

declare(strict_types=1);

use Core\database\ConnectionManager;
use Core\database\SchemaBuilder;

return static function (SchemaBuilder $schema, array $prefixes): void {
    $core = (string) ($prefixes['core'] ?? 'core_');
    $table = $core . 'db_versions';
    $manager = new ConnectionManager();
    $pdo = $manager->connection();
    $driver = $manager->driver();

    try {
        if ($driver === 'sqlite') {
            $columns = $pdo->query('PRAGMA table_info(' . $table . ')');
            $found = false;
            if ($columns !== false) {
                foreach ($columns->fetchAll(\PDO::FETCH_ASSOC) ?: [] as $row) {
                    if ((string) ($row['name'] ?? '') === 'checksum') {
                        $found = true;
                        break;
                    }
                }
            }
            if (!$found) {
                $pdo->exec('ALTER TABLE ' . $table . ' ADD COLUMN checksum TEXT NULL');
            }
            return;
        }

        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table_name AND COLUMN_NAME = :column_name'
        );
        $stmt->execute([
            'table_name' => $table,
            'column_name' => 'checksum',
        ]);
        $exists = (int) ($stmt->fetchColumn() ?: 0) > 0;
        if (!$exists) {
            $pdo->exec('ALTER TABLE ' . $table . ' ADD COLUMN checksum VARCHAR(191) NULL');
        }
    } catch (Throwable) {
        // Non bloquant: migration best effort
    }
};
