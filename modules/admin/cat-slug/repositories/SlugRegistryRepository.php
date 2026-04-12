<?php

declare(strict_types=1);

namespace Modules\CatSlug\repositories;

use Core\database\ConnectionManager;
use PDO;

final class SlugRegistryRepository
{
    private PDO $pdo;
    private bool $schemaEnsured = false;

    public function __construct()
    {
        $this->pdo = (new ConnectionManager())->connection();
        $this->ensureSchema();
    }

    private function ensureSchema(): void
    {
        if ($this->schemaEnsured) {
            return;
        }

        $driver = (string) $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'sqlite') {
            $this->pdo->exec(
                'CREATE TABLE IF NOT EXISTS mod_cat_slug_registry ('
                . 'id INTEGER PRIMARY KEY AUTOINCREMENT,'
                . 'entity_type VARCHAR(80) NOT NULL,'
                . 'entity_id INTEGER NOT NULL,'
                . 'slug VARCHAR(191) NOT NULL,'
                . 'scope_key VARCHAR(120) NOT NULL DEFAULT "global",'
                . 'is_primary INTEGER NOT NULL DEFAULT 1,'
                . 'created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,'
                . 'updated_at DATETIME NULL)'
            );
            $this->pdo->exec('CREATE UNIQUE INDEX IF NOT EXISTS ux_mod_cat_slug_scope_slug ON mod_cat_slug_registry(scope_key, slug)');
            $this->pdo->exec('CREATE UNIQUE INDEX IF NOT EXISTS ux_mod_cat_slug_entity_primary ON mod_cat_slug_registry(entity_type, entity_id, is_primary)');
            $this->pdo->exec('CREATE INDEX IF NOT EXISTS ix_mod_cat_slug_entity ON mod_cat_slug_registry(entity_type, entity_id)');
        } else {
            $this->pdo->exec(
                'CREATE TABLE IF NOT EXISTS mod_cat_slug_registry ('
                . 'id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,'
                . 'entity_type VARCHAR(80) NOT NULL,'
                . 'entity_id BIGINT UNSIGNED NOT NULL,'
                . 'slug VARCHAR(191) NOT NULL,'
                . 'scope_key VARCHAR(120) NOT NULL DEFAULT "global",'
                . 'is_primary TINYINT(1) NOT NULL DEFAULT 1,'
                . 'created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,'
                . 'updated_at DATETIME NULL,'
                . 'UNIQUE KEY ux_mod_cat_slug_scope_slug (scope_key, slug),'
                . 'UNIQUE KEY ux_mod_cat_slug_entity_primary (entity_type, entity_id, is_primary),'
                . 'KEY ix_mod_cat_slug_entity (entity_type, entity_id)'
                . ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
            );
        }

        $this->schemaEnsured = true;
    }

    public function exists(string $slug, string $scopeKey, ?array $excludeEntity = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM mod_cat_slug_registry WHERE slug = :slug AND scope_key = :scope';
        $params = ['slug' => $slug, 'scope' => $scopeKey];

        if (is_array($excludeEntity)) {
            $sql .= ' AND NOT (entity_type = :etype AND entity_id = :eid)';
            $params['etype'] = (string) ($excludeEntity['entity_type'] ?? '');
            $params['eid'] = (int) ($excludeEntity['entity_id'] ?? 0);
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function reserve(string $entityType, int $entityId, string $slug, string $scopeKey, bool $primary = true): bool
    {
        if ($entityId <= 0 || $entityType === '' || $slug === '' || $scopeKey === '') {
            return false;
        }

        if ($primary) {
            $clear = $this->pdo->prepare('UPDATE mod_cat_slug_registry SET is_primary = 0, updated_at = CURRENT_TIMESTAMP WHERE entity_type = :etype AND entity_id = :eid AND scope_key = :scope');
            $clear->execute(['etype' => $entityType, 'eid' => $entityId, 'scope' => $scopeKey]);
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO mod_cat_slug_registry (entity_type, entity_id, slug, scope_key, is_primary, created_at) VALUES (:etype, :eid, :slug, :scope, :primary, CURRENT_TIMESTAMP)'
        );

        return $stmt->execute([
            'etype' => $entityType,
            'eid' => $entityId,
            'slug' => $slug,
            'scope' => $scopeKey,
            'primary' => $primary ? 1 : 0,
        ]);
    }

    public function recent(int $limit = 50): array
    {
        $limit = max(1, min(500, $limit));
        $stmt = $this->pdo->query('SELECT id, entity_type, entity_id, slug, scope_key, is_primary, created_at, updated_at FROM mod_cat_slug_registry ORDER BY id DESC LIMIT ' . $limit);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
