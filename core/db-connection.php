<?php

declare(strict_types=1);

use Core\database\ConnectionManager;

require_once CATMIN_CORE . '/database/ConnectionManager.php';
require_once CATMIN_CORE . '/database/DriverResolver.php';

final class CoreDbConnection
{
    private ConnectionManager $manager;

    public function __construct(?ConnectionManager $manager = null)
    {
        $this->manager = $manager ?? new ConnectionManager();
    }

    public function get(?string $name = null): PDO
    {
        return $this->manager->connection($name);
    }

    public function driver(?string $name = null): string
    {
        return $this->manager->driver($name);
    }
}
