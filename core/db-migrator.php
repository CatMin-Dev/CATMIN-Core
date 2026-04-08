<?php

declare(strict_types=1);

use Core\database\MigrationRunner;

require_once CATMIN_CORE . '/database/MigrationRunner.php';
require_once CATMIN_CORE . '/database/SchemaBuilder.php';
require_once CATMIN_CORE . '/db-connection.php';

final class CoreDbMigrator
{
    private MigrationRunner $runner;

    public function __construct(?MigrationRunner $runner = null)
    {
        $this->runner = $runner ?? new MigrationRunner();
    }

    public function run(?string $connectionName = null): array
    {
        return $this->runner->run($connectionName);
    }
}

