<?php

declare(strict_types=1);

namespace Modules\CatTags\repositories;

use Core\database\ConnectionManager;
use PDO;

final class TagsRepository
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
            $this->pdo->exec('CREATE TABLE IF NOT EXISTS mod_cat_tags_tags (id INTEGER PRIMARY KEY AUTOINCREMENT, name VARCHAR(120) NOT NULL, slug VARCHAR(160) NOT NULL, usage_count INTEGER NOT NULL DEFAULT 0, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NULL)');
            $this->pdo->exec('CREATE UNIQUE INDEX IF NOT EXISTS ux_mod_cat_tags_slug ON mod_cat_tags_tags(slug)');
            $this->pdo->exec('CREATE INDEX IF NOT EXISTS ix_mod_cat_tags_name ON mod_cat_tags_tags(name)');
            $this->pdo->exec('CREATE TABLE IF NOT EXISTS mod_cat_tags_links (id INTEGER PRIMARY KEY AUTOINCREMENT, tag_id INTEGER NOT NULL, entity_type VARCHAR(80) NOT NULL, entity_id INTEGER NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP)');
            $this->pdo->exec('CREATE UNIQUE INDEX IF NOT EXISTS ux_mod_cat_tags_link ON mod_cat_tags_links(tag_id, entity_type, entity_id)');
            $this->pdo->exec('CREATE INDEX IF NOT EXISTS ix_mod_cat_tags_entity ON mod_cat_tags_links(entity_type, entity_id)');
            $this->pdo->exec('CREATE INDEX IF NOT EXISTS ix_mod_cat_tags_tag ON mod_cat_tags_links(tag_id)');
        } else {
            $this->pdo->exec('CREATE TABLE IF NOT EXISTS mod_cat_tags_tags (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(120) NOT NULL, slug VARCHAR(160) NOT NULL, usage_count INT NOT NULL DEFAULT 0, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NULL, UNIQUE KEY ux_mod_cat_tags_slug (slug), KEY ix_mod_cat_tags_name (name)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
            $this->pdo->exec('CREATE TABLE IF NOT EXISTS mod_cat_tags_links (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, tag_id BIGINT UNSIGNED NOT NULL, entity_type VARCHAR(80) NOT NULL, entity_id BIGINT UNSIGNED NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY ux_mod_cat_tags_link (tag_id, entity_type, entity_id), KEY ix_mod_cat_tags_entity (entity_type, entity_id), KEY ix_mod_cat_tags_tag (tag_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        }

        $this->schemaEnsured = true;
    }

    public function findTagBySlug(string $slug): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, name, slug, usage_count, created_at, updated_at FROM mod_cat_tags_tags WHERE slug = :slug LIMIT 1');
        $stmt->execute(['slug' => $slug]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return is_array($row) ? $row : null;
    }

    public function createTag(string $name, string $slug): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO mod_cat_tags_tags (name, slug, usage_count, created_at, updated_at) VALUES (:name, :slug, 0, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)');
        $stmt->execute(['name' => $name, 'slug' => $slug]);
        return (int) $this->pdo->lastInsertId();
    }

    public function searchTags(string $query, int $limit = 15): array
    {
        $limit = max(1, min(100, $limit));
        $stmt = $this->pdo->prepare('SELECT id, name, slug, usage_count FROM mod_cat_tags_tags WHERE name LIKE :q OR slug LIKE :q ORDER BY usage_count DESC, name ASC LIMIT ' . $limit);
        $stmt->execute(['q' => '%' . $query . '%']);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function allTags(string $query = '', int $limit = 100): array
    {
        $limit = max(1, min(300, $limit));
        if ($query !== '') {
            return $this->searchTags($query, $limit);
        }
        $stmt = $this->pdo->query('SELECT id, name, slug, usage_count, updated_at FROM mod_cat_tags_tags ORDER BY usage_count DESC, name ASC LIMIT ' . $limit);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function linkTag(int $tagId, string $entityType, int $entityId): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO mod_cat_tags_links (tag_id, entity_type, entity_id, created_at) VALUES (:tag_id, :entity_type, :entity_id, CURRENT_TIMESTAMP)');
        $stmt->execute(['tag_id' => $tagId, 'entity_type' => $entityType, 'entity_id' => $entityId]);
    }

    public function unlinkEntity(string $entityType, int $entityId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM mod_cat_tags_links WHERE entity_type = :entity_type AND entity_id = :entity_id');
        $stmt->execute(['entity_type' => $entityType, 'entity_id' => $entityId]);
    }

    public function entityTags(string $entityType, int $entityId): array
    {
        $stmt = $this->pdo->prepare('SELECT t.id, t.name, t.slug FROM mod_cat_tags_links l JOIN mod_cat_tags_tags t ON t.id = l.tag_id WHERE l.entity_type = :entity_type AND l.entity_id = :entity_id ORDER BY t.name ASC');
        $stmt->execute(['entity_type' => $entityType, 'entity_id' => $entityId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function refreshUsageCount(): void
    {
        $driver = (string) $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            $this->pdo->exec('UPDATE mod_cat_tags_tags SET usage_count = (SELECT COUNT(*) FROM mod_cat_tags_links l WHERE l.tag_id = mod_cat_tags_tags.id), updated_at = CURRENT_TIMESTAMP');
            return;
        }
        $this->pdo->exec('UPDATE mod_cat_tags_tags t LEFT JOIN (SELECT tag_id, COUNT(*) cnt FROM mod_cat_tags_links GROUP BY tag_id) c ON c.tag_id = t.id SET t.usage_count = COALESCE(c.cnt,0), t.updated_at = CURRENT_TIMESTAMP');
    }

    public function stats(): array
    {
        $total = (int) $this->pdo->query('SELECT COUNT(*) FROM mod_cat_tags_tags')->fetchColumn();
        $links = (int) $this->pdo->query('SELECT COUNT(*) FROM mod_cat_tags_links')->fetchColumn();
        $used = (int) $this->pdo->query('SELECT COUNT(*) FROM mod_cat_tags_tags WHERE usage_count > 0')->fetchColumn();
        return ['total_tags' => $total, 'total_links' => $links, 'used_tags' => $used];
    }
}
