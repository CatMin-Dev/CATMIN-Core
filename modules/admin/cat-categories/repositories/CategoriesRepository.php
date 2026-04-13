<?php

declare(strict_types=1);

namespace Modules\CatCategories\repositories;

use Core\database\ConnectionManager;
use PDO;

final class CategoriesRepository
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
            $this->pdo->exec('CREATE TABLE IF NOT EXISTS mod_cat_categories_categories (id INTEGER PRIMARY KEY AUTOINCREMENT, name VARCHAR(140) NOT NULL, slug VARCHAR(180) NOT NULL, parent_id INTEGER NULL, sort_order INTEGER NOT NULL DEFAULT 0, usage_count INTEGER NOT NULL DEFAULT 0, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NULL)');
            $this->pdo->exec('CREATE UNIQUE INDEX IF NOT EXISTS ux_mod_cat_categories_slug ON mod_cat_categories_categories(slug)');
            $this->pdo->exec('CREATE INDEX IF NOT EXISTS ix_mod_cat_categories_parent ON mod_cat_categories_categories(parent_id)');
            $this->pdo->exec('CREATE INDEX IF NOT EXISTS ix_mod_cat_categories_sort ON mod_cat_categories_categories(sort_order)');
            $this->pdo->exec('CREATE TABLE IF NOT EXISTS mod_cat_categories_links (id INTEGER PRIMARY KEY AUTOINCREMENT, category_id INTEGER NOT NULL, entity_type VARCHAR(80) NOT NULL, entity_id INTEGER NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP)');
            $this->pdo->exec('CREATE UNIQUE INDEX IF NOT EXISTS ux_mod_cat_categories_link ON mod_cat_categories_links(category_id, entity_type, entity_id)');
            $this->pdo->exec('CREATE INDEX IF NOT EXISTS ix_mod_cat_categories_entity ON mod_cat_categories_links(entity_type, entity_id)');
            $this->pdo->exec('CREATE INDEX IF NOT EXISTS ix_mod_cat_categories_category ON mod_cat_categories_links(category_id)');
        } else {
            $this->pdo->exec('CREATE TABLE IF NOT EXISTS mod_cat_categories_categories (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(140) NOT NULL, slug VARCHAR(180) NOT NULL, parent_id BIGINT UNSIGNED NULL, sort_order INT NOT NULL DEFAULT 0, usage_count INT NOT NULL DEFAULT 0, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NULL, UNIQUE KEY ux_mod_cat_categories_slug (slug), KEY ix_mod_cat_categories_parent (parent_id), KEY ix_mod_cat_categories_sort (sort_order)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
            $this->pdo->exec('CREATE TABLE IF NOT EXISTS mod_cat_categories_links (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, category_id BIGINT UNSIGNED NOT NULL, entity_type VARCHAR(80) NOT NULL, entity_id BIGINT UNSIGNED NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY ux_mod_cat_categories_link (category_id, entity_type, entity_id), KEY ix_mod_cat_categories_entity (entity_type, entity_id), KEY ix_mod_cat_categories_category (category_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        }

        $this->schemaEnsured = true;
    }

    public function createCategory(string $name, string $slug, ?int $parentId, int $sortOrder): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO mod_cat_categories_categories (name, slug, parent_id, sort_order, usage_count, created_at, updated_at) VALUES (:name, :slug, :parent_id, :sort_order, 0, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)');
        $stmt->execute([
            'name' => $name,
            'slug' => $slug,
            'parent_id' => $parentId,
            'sort_order' => $sortOrder,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function bySlug(string $slug): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, name, slug, parent_id, sort_order, usage_count FROM mod_cat_categories_categories WHERE slug = :slug LIMIT 1');
        $stmt->execute(['slug' => $slug]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return is_array($row) ? $row : null;
    }

    public function allCategories(): array
    {
        $stmt = $this->pdo->query('SELECT id, name, slug, parent_id, sort_order, usage_count, updated_at FROM mod_cat_categories_categories ORDER BY COALESCE(parent_id,0) ASC, sort_order ASC, name ASC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function unlinkEntity(string $entityType, int $entityId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM mod_cat_categories_links WHERE entity_type = :entity_type AND entity_id = :entity_id');
        $stmt->execute(['entity_type' => $entityType, 'entity_id' => $entityId]);
    }

    public function linkCategory(int $categoryId, string $entityType, int $entityId): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO mod_cat_categories_links (category_id, entity_type, entity_id, created_at) VALUES (:category_id, :entity_type, :entity_id, CURRENT_TIMESTAMP)');
        $stmt->execute(['category_id' => $categoryId, 'entity_type' => $entityType, 'entity_id' => $entityId]);
    }

    public function entityCategoryIds(string $entityType, int $entityId): array
    {
        $stmt = $this->pdo->prepare('SELECT category_id FROM mod_cat_categories_links WHERE entity_type = :entity_type AND entity_id = :entity_id ORDER BY id ASC');
        $stmt->execute(['entity_type' => $entityType, 'entity_id' => $entityId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        return array_values(array_map(static fn (array $r): int => (int) ($r['category_id'] ?? 0), $rows));
    }

    public function refreshUsageCount(): void
    {
        $driver = (string) $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            $this->pdo->exec('UPDATE mod_cat_categories_categories SET usage_count = (SELECT COUNT(*) FROM mod_cat_categories_links l WHERE l.category_id = mod_cat_categories_categories.id), updated_at = CURRENT_TIMESTAMP');
            return;
        }
        $this->pdo->exec('UPDATE mod_cat_categories_categories c LEFT JOIN (SELECT category_id, COUNT(*) cnt FROM mod_cat_categories_links GROUP BY category_id) x ON x.category_id = c.id SET c.usage_count = COALESCE(x.cnt,0), c.updated_at = CURRENT_TIMESTAMP');
    }

    public function stats(): array
    {
        $total = (int) $this->pdo->query('SELECT COUNT(*) FROM mod_cat_categories_categories')->fetchColumn();
        $links = (int) $this->pdo->query('SELECT COUNT(*) FROM mod_cat_categories_links')->fetchColumn();
        $roots = (int) $this->pdo->query('SELECT COUNT(*) FROM mod_cat_categories_categories WHERE parent_id IS NULL')->fetchColumn();
        return ['total_categories' => $total, 'total_links' => $links, 'root_categories' => $roots];
    }
}
