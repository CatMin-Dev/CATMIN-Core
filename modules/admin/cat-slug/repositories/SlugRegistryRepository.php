<?php

declare(strict_types=1);

namespace Modules\CatSlug\repositories;

use Core\database\ConnectionManager;
use PDO;

final class SlugRegistryRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = (new ConnectionManager())->connection();
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
