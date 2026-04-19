<?php

declare(strict_types=1);

use Core\database\ConnectionManager;

return static function (): void {
    require_once CATMIN_CORE . '/database/ConnectionManager.php';

    $manager = new ConnectionManager();
    $pdo = $manager->connection();
    $driver = $manager->driver();

    if ($driver === 'pgsql') {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS cat_contract_demo_records ('
            . 'id SERIAL PRIMARY KEY,'
            . 'title VARCHAR(190) NOT NULL,'
            . 'status VARCHAR(50) NOT NULL DEFAULT \'draft\','
            . 'created_at TIMESTAMP NULL,'
            . 'updated_at TIMESTAMP NULL'
            . ')'
        );
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_cat_contract_demo_records_status ON cat_contract_demo_records(status)');
        return;
    }

    if ($driver === 'sqlite') {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS cat_contract_demo_records ('
            . 'id INTEGER PRIMARY KEY AUTOINCREMENT,'
            . 'title TEXT NOT NULL,'
            . 'status TEXT NOT NULL DEFAULT \'draft\','
            . 'created_at TEXT NULL,'
            . 'updated_at TEXT NULL'
            . ')'
        );
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_cat_contract_demo_records_status ON cat_contract_demo_records(status)');
        return;
    }

    // mysql/mariadb and compatible drivers
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS cat_contract_demo_records ('
        . 'id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,'
        . 'title VARCHAR(190) NOT NULL,'
        . 'status VARCHAR(50) NOT NULL DEFAULT \'draft\','
        . 'created_at DATETIME NULL,'
        . 'updated_at DATETIME NULL,'
        . 'INDEX idx_cat_contract_demo_records_status (status)'
        . ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
};
