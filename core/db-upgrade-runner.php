<?php

declare(strict_types=1);

require_once CATMIN_CORE . '/db-migrator.php';
require_once CATMIN_CORE . '/db-seeder.php';
require_once CATMIN_CORE . '/db-version-manager.php';

final class CoreDbUpgradeRunner
{
    private CoreDbMigrator $migrator;
    private CoreDbSeeder $seeder;
    private CoreDbVersionManager $versions;

    public function __construct(
        ?CoreDbMigrator $migrator = null,
        ?CoreDbSeeder $seeder = null,
        ?CoreDbVersionManager $versions = null
    ) {
        $this->migrator = $migrator ?? new CoreDbMigrator();
        $this->seeder = $seeder ?? new CoreDbSeeder();
        $this->versions = $versions ?? new CoreDbVersionManager();
    }

    public function run(?string $connectionName = null): array
    {
        $executed = $this->migrator->run($connectionName);
        $this->seeder->runBase($connectionName);

        $result = [
            'migrations' => $executed,
            'applied_count' => count($executed),
            'db_version' => $this->versions->currentSchemaVersion($connectionName),
            'expected_db_version' => $this->versions->expectedSchemaVersion(),
            'last_migration' => $this->versions->lastMigration($connectionName),
        ];

        Core\logs\Logger::info('DB upgrade runner executed', [
            'connection' => $connectionName ?? 'default',
            'applied_count' => $result['applied_count'],
            'db_version' => $result['db_version'],
            'expected_db_version' => $result['expected_db_version'],
        ]);

        return $result;
    }
}
