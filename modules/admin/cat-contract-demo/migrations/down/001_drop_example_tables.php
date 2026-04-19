<?php

declare(strict_types=1);

use Core\database\ConnectionManager;

return static function (): void {
    require_once CATMIN_CORE . '/database/ConnectionManager.php';

    $manager = new ConnectionManager();
    $pdo = $manager->connection();
    $pdo->exec('DROP TABLE IF EXISTS cat_contract_demo_records');
};
