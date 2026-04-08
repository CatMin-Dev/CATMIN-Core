<?php

declare(strict_types=1);

require_once CATMIN_CORE . '/db-connection.php';
require_once CATMIN_CORE . '/db-query.php';
require_once CATMIN_CORE . '/db-migrator.php';
require_once CATMIN_CORE . '/db-seeder.php';
require_once CATMIN_CORE . '/db-version-manager.php';
require_once CATMIN_CORE . '/db-upgrade-runner.php';

final class CoreDbEngine
{
    private CoreDbConnection $connection;
    private CoreDbQuery $query;
    private CoreDbUpgradeRunner $upgrade;
    private CoreDbVersionManager $versions;

    public function __construct()
    {
        $this->connection = new CoreDbConnection();
        $this->query = new CoreDbQuery($this->connection);
        $this->versions = new CoreDbVersionManager($this->connection);
        $this->upgrade = new CoreDbUpgradeRunner(new CoreDbMigrator(), new CoreDbSeeder(), $this->versions);
    }

    public function connection(): CoreDbConnection
    {
        return $this->connection;
    }

    public function query(): CoreDbQuery
    {
        return $this->query;
    }

    public function upgrade(?string $connectionName = null): array
    {
        return $this->upgrade->run($connectionName);
    }

    public function versions(): CoreDbVersionManager
    {
        return $this->versions;
    }
}

