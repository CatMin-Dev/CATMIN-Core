<?php

declare(strict_types=1);

use Core\database\SeederRunner;

require_once CATMIN_CORE . '/database/SeederRunner.php';
require_once CATMIN_CORE . '/db-connection.php';

final class CoreDbSeeder
{
    private SeederRunner $runner;

    public function __construct(?SeederRunner $runner = null)
    {
        $this->runner = $runner ?? new SeederRunner();
    }

    public function runBase(?string $connectionName = null): void
    {
        $this->runner->runBase($connectionName);
    }
}

