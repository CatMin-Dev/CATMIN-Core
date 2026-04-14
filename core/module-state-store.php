<?php

declare(strict_types=1);

use Core\database\ConnectionManager;

final class CoreModuleStateStore
{
    private const DB_STATES = [
        'installed',
        'migrated',
        'enabled',
        'disabled',
        'uninstalled_keep_data',
        'uninstalled_drop_data',
    ];

    public function stateBySlug(): array
    {
        try {
            $pdo = (new ConnectionManager())->connection();
            $table = (string) config('database.prefixes.core', 'core_') . 'modules';
            $stmt = $pdo->query('SELECT slug, status, enabled, db_state, schema_version, last_migration, version, updated_at, installed_at FROM ' . $table);
            $rows = $stmt !== false ? ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
            $state = [];
            foreach ($rows as $row) {
                $slug = strtolower(trim((string) ($row['slug'] ?? '')));
                if ($slug === '') {
                    continue;
                }
                $state[$slug] = $row;
            }
            return $state;
        } catch (Throwable) {
            return [];
        }
    }

    public function persist(
        string $slug,
        string $name,
        string $version,
        bool $enabled,
        ?string $dbState = null,
        ?string $schemaVersion = null,
        ?string $lastMigration = null
    ): void
    {
        try {
            $pdo = (new ConnectionManager())->connection();
            $table = (string) config('database.prefixes.core', 'core_') . 'modules';
            $normalizedDbState = $this->normalizeDbState($dbState ?? ($enabled ? 'enabled' : 'disabled'));
            $schemaVersion = trim((string) ($schemaVersion ?? ''));
            $lastMigration = trim((string) ($lastMigration ?? ''));

            $check = $pdo->prepare('SELECT id FROM ' . $table . ' WHERE slug = :slug LIMIT 1');
            $check->execute(['slug' => $slug]);
            $id = $check->fetchColumn();

            if ($id === false) {
                $insert = $pdo->prepare(
                    'INSERT INTO ' . $table . ' (name, slug, version, status, enabled, db_state, schema_version, last_migration, installed_at, updated_at) VALUES (:name, :slug, :version, :status, :enabled, :db_state, :schema_version, :last_migration, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
                );
                $insert->execute([
                    'name' => $name,
                    'slug' => $slug,
                    'version' => $version,
                    'status' => $enabled ? 'active' : 'inactive',
                    'enabled' => $enabled ? 1 : 0,
                    'db_state' => $normalizedDbState,
                    'schema_version' => $schemaVersion,
                    'last_migration' => $lastMigration,
                ]);
                return;
            }

            $update = $pdo->prepare(
                'UPDATE ' . $table . ' SET name = :name, version = :version, status = :status, enabled = :enabled, db_state = :db_state, schema_version = :schema_version, last_migration = :last_migration, updated_at = CURRENT_TIMESTAMP WHERE id = :id'
            );
            $update->execute([
                'id' => (int) $id,
                'name' => $name,
                'version' => $version,
                'status' => $enabled ? 'active' : 'inactive',
                'enabled' => $enabled ? 1 : 0,
                'db_state' => $normalizedDbState,
                'schema_version' => $schemaVersion,
                'last_migration' => $lastMigration,
            ]);
        } catch (Throwable) {
        }
    }

    public function markLifecycle(string $slug, string $dbState, ?string $lastMigration = null): void
    {
        $slug = strtolower(trim($slug));
        if ($slug === '') {
            return;
        }

        $normalizedDbState = $this->normalizeDbState($dbState);
        $enabled = $normalizedDbState === 'enabled';
        $status = $enabled ? 'active' : 'inactive';
        $lastMigration = trim((string) ($lastMigration ?? ''));

        try {
            $pdo = (new ConnectionManager())->connection();
            $table = (string) config('database.prefixes.core', 'core_') . 'modules';
            $stmt = $pdo->prepare(
                'UPDATE ' . $table . ' SET status = :status, enabled = :enabled, db_state = :db_state, last_migration = :last_migration, updated_at = CURRENT_TIMESTAMP WHERE slug = :slug'
            );
            $stmt->execute([
                'slug' => $slug,
                'status' => $status,
                'enabled' => $enabled ? 1 : 0,
                'db_state' => $normalizedDbState,
                'last_migration' => $lastMigration,
            ]);
        } catch (Throwable) {
        }
    }

    private function normalizeDbState(string $dbState): string
    {
        $dbState = strtolower(trim($dbState));
        if ($dbState === 'active') {
            $dbState = 'enabled';
        } elseif ($dbState === 'inactive') {
            $dbState = 'disabled';
        }
        if (!in_array($dbState, self::DB_STATES, true)) {
            return 'disabled';
        }

        return $dbState;
    }
}

