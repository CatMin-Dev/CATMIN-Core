<?php

declare(strict_types=1);

namespace Modules\CatSeoMeta\repositories;

use Core\database\ConnectionManager;
use PDO;

final class SeoMetaRepository
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
                'CREATE TABLE IF NOT EXISTS mod_cat_seo_meta ('
                . 'id INTEGER PRIMARY KEY AUTOINCREMENT,'
                . 'entity_type VARCHAR(80) NOT NULL,'
                . 'entity_id INTEGER NOT NULL,'
                . 'seo_title VARCHAR(191) NULL,'
                . 'meta_description TEXT NULL,'
                . 'canonical_url VARCHAR(255) NULL,'
                . 'robots_index INTEGER NOT NULL DEFAULT 1,'
                . 'robots_follow INTEGER NOT NULL DEFAULT 1,'
                . 'og_title VARCHAR(191) NULL,'
                . 'og_description TEXT NULL,'
                . 'og_image_media_id INTEGER NULL,'
                . 'focus_keyword VARCHAR(120) NULL,'
                . 'seo_score INTEGER NOT NULL DEFAULT 0,'
                . 'seo_flags_json TEXT NULL,'
                . 'created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,'
                . 'updated_at DATETIME NULL)'
            );
            $this->pdo->exec('CREATE UNIQUE INDEX IF NOT EXISTS ux_mod_cat_seo_entity ON mod_cat_seo_meta(entity_type, entity_id)');
            $this->pdo->exec('CREATE INDEX IF NOT EXISTS ix_mod_cat_seo_score ON mod_cat_seo_meta(seo_score)');
            $this->pdo->exec('CREATE INDEX IF NOT EXISTS ix_mod_cat_seo_entity_type ON mod_cat_seo_meta(entity_type)');
        } else {
            $this->pdo->exec(
                'CREATE TABLE IF NOT EXISTS mod_cat_seo_meta ('
                . 'id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,'
                . 'entity_type VARCHAR(80) NOT NULL,'
                . 'entity_id BIGINT UNSIGNED NOT NULL,'
                . 'seo_title VARCHAR(191) NULL,'
                . 'meta_description TEXT NULL,'
                . 'canonical_url VARCHAR(255) NULL,'
                . 'robots_index TINYINT(1) NOT NULL DEFAULT 1,'
                . 'robots_follow TINYINT(1) NOT NULL DEFAULT 1,'
                . 'og_title VARCHAR(191) NULL,'
                . 'og_description TEXT NULL,'
                . 'og_image_media_id BIGINT UNSIGNED NULL,'
                . 'focus_keyword VARCHAR(120) NULL,'
                . 'seo_score INT NOT NULL DEFAULT 0,'
                . 'seo_flags_json LONGTEXT NULL,'
                . 'created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,'
                . 'updated_at DATETIME NULL,'
                . 'UNIQUE KEY ux_mod_cat_seo_entity (entity_type, entity_id),'
                . 'KEY ix_mod_cat_seo_score (seo_score),'
                . 'KEY ix_mod_cat_seo_entity_type (entity_type)'
                . ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
            );
        }

        $this->schemaEnsured = true;
    }

    public function find(string $entityType, int $entityId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM mod_cat_seo_meta WHERE entity_type = :entity_type AND entity_id = :entity_id LIMIT 1');
        $stmt->execute(['entity_type' => $entityType, 'entity_id' => $entityId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return is_array($row) ? $row : null;
    }

    public function upsert(array $payload): void
    {
        $current = $this->find((string) $payload['entity_type'], (int) $payload['entity_id']);
        if ($current === null) {
            $sql = 'INSERT INTO mod_cat_seo_meta (entity_type, entity_id, seo_title, meta_description, canonical_url, robots_index, robots_follow, og_title, og_description, og_image_media_id, focus_keyword, seo_score, seo_flags_json, created_at, updated_at) VALUES (:entity_type, :entity_id, :seo_title, :meta_description, :canonical_url, :robots_index, :robots_follow, :og_title, :og_description, :og_image_media_id, :focus_keyword, :seo_score, :seo_flags_json, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)';
        } else {
            $sql = 'UPDATE mod_cat_seo_meta SET seo_title = :seo_title, meta_description = :meta_description, canonical_url = :canonical_url, robots_index = :robots_index, robots_follow = :robots_follow, og_title = :og_title, og_description = :og_description, og_image_media_id = :og_image_media_id, focus_keyword = :focus_keyword, seo_score = :seo_score, seo_flags_json = :seo_flags_json, updated_at = CURRENT_TIMESTAMP WHERE entity_type = :entity_type AND entity_id = :entity_id';
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'entity_type' => (string) $payload['entity_type'],
            'entity_id' => (int) $payload['entity_id'],
            'seo_title' => $this->nullableString($payload['seo_title'] ?? null),
            'meta_description' => $this->nullableString($payload['meta_description'] ?? null),
            'canonical_url' => $this->nullableString($payload['canonical_url'] ?? null),
            'robots_index' => !empty($payload['robots_index']) ? 1 : 0,
            'robots_follow' => !empty($payload['robots_follow']) ? 1 : 0,
            'og_title' => $this->nullableString($payload['og_title'] ?? null),
            'og_description' => $this->nullableString($payload['og_description'] ?? null),
            'og_image_media_id' => $this->nullableInt($payload['og_image_media_id'] ?? null),
            'focus_keyword' => $this->nullableString($payload['focus_keyword'] ?? null),
            'seo_score' => (int) ($payload['seo_score'] ?? 0),
            'seo_flags_json' => $this->nullableString($payload['seo_flags_json'] ?? null),
        ]);
    }

    public function stats(): array
    {
        $total = (int) $this->pdo->query('SELECT COUNT(*) FROM mod_cat_seo_meta')->fetchColumn();
        $avgScore = (float) $this->pdo->query('SELECT COALESCE(AVG(seo_score), 0) FROM mod_cat_seo_meta')->fetchColumn();
        $needAttention = (int) $this->pdo->query('SELECT COUNT(*) FROM mod_cat_seo_meta WHERE seo_score < 60 OR seo_title IS NULL OR meta_description IS NULL')->fetchColumn();

        return [
            'total' => $total,
            'avg_score' => (int) round($avgScore),
            'need_attention' => $needAttention,
        ];
    }

    public function needsAttention(int $limit = 25): array
    {
        $limit = max(1, min(200, $limit));
        $stmt = $this->pdo->query(
            'SELECT entity_type, entity_id, seo_title, meta_description, seo_score, updated_at '
            . 'FROM mod_cat_seo_meta '
            . 'WHERE seo_score < 60 OR seo_title IS NULL OR meta_description IS NULL '
            . 'ORDER BY seo_score ASC, updated_at DESC '
            . 'LIMIT ' . $limit
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function recent(int $limit = 50): array
    {
        $limit = max(1, min(300, $limit));
        $stmt = $this->pdo->query(
            'SELECT entity_type, entity_id, seo_title, meta_description, focus_keyword, seo_score, updated_at '
            . 'FROM mod_cat_seo_meta ORDER BY updated_at DESC LIMIT ' . $limit
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function nullableString(mixed $value): ?string
    {
        $v = trim((string) $value);
        return $v === '' ? null : $v;
    }

    private function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '' || !is_numeric($value)) {
            return null;
        }
        $n = (int) $value;
        return $n > 0 ? $n : null;
    }
}
