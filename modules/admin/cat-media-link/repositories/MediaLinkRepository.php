<?php

declare(strict_types=1);

namespace Modules\CatMediaLink\repositories;

use Core\database\ConnectionManager;
use PDO;

final class MediaLinkRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = (new ConnectionManager())->connection();
    }

    public function stats(): array
    {
        return [
            'assets' => (int) $this->scalar('SELECT COUNT(*) FROM mod_cat_media_link_assets'),
            'links' => (int) $this->scalar('SELECT COUNT(*) FROM mod_cat_media_link_links'),
            'featured' => (int) $this->scalar("SELECT COUNT(*) FROM mod_cat_media_link_links WHERE link_type = 'featured' OR is_primary = 1"),
        ];
    }

    public function listAssets(int $limit = 120): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM mod_cat_media_link_assets ORDER BY id DESC LIMIT :limit');
        $stmt->bindValue(':limit', max(1, min(600, $limit)), PDO::PARAM_INT);
        $stmt->execute();
        return (array) $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createAsset(array $payload): array
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO mod_cat_media_link_assets (media_type, source_type, storage_path, public_url, mime_type, size_bytes, width, height, duration_seconds, title, alt_text, created_at, updated_at)
             VALUES (:media_type, :source_type, :storage_path, :public_url, :mime_type, :size_bytes, :width, :height, :duration_seconds, :title, :alt_text, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        );

        $ok = $stmt->execute([
            'media_type' => (string) ($payload['media_type'] ?? 'image'),
            'source_type' => (string) ($payload['source_type'] ?? 'upload'),
            'storage_path' => $payload['storage_path'] ?? null,
            'public_url' => $payload['public_url'] ?? null,
            'mime_type' => $payload['mime_type'] ?? null,
            'size_bytes' => (int) ($payload['size_bytes'] ?? 0),
            'width' => isset($payload['width']) ? (int) $payload['width'] : null,
            'height' => isset($payload['height']) ? (int) $payload['height'] : null,
            'duration_seconds' => isset($payload['duration_seconds']) ? (int) $payload['duration_seconds'] : null,
            'title' => $payload['title'] ?? null,
            'alt_text' => $payload['alt_text'] ?? null,
        ]);

        return ['ok' => $ok, 'id' => $ok ? (int) $this->pdo->lastInsertId() : 0];
    }

    public function listUsage(int $mediaId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM mod_cat_media_link_links WHERE media_id = :media_id ORDER BY entity_type ASC, entity_id ASC, sort_order ASC');
        $stmt->execute(['media_id' => $mediaId]);
        return (array) $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listLatestUsages(int $limit = 120): array
    {
        $stmt = $this->pdo->prepare('SELECT l.*, a.public_url, a.media_type FROM mod_cat_media_link_links l LEFT JOIN mod_cat_media_link_assets a ON a.id = l.media_id ORDER BY l.id DESC LIMIT :limit');
        $stmt->bindValue(':limit', max(1, min(600, $limit)), PDO::PARAM_INT);
        $stmt->execute();
        return (array) $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function syncEntityLinks(string $entityType, int $entityId, array $links): array
    {
        if ($entityType === '' || $entityId <= 0) {
            return ['ok' => false, 'message' => 'Entity invalide.'];
        }

        $this->pdo->beginTransaction();
        try {
            $clear = $this->pdo->prepare('DELETE FROM mod_cat_media_link_links WHERE entity_type = :entity_type AND entity_id = :entity_id');
            $clear->execute(['entity_type' => $entityType, 'entity_id' => $entityId]);

            $insert = $this->pdo->prepare(
                'INSERT INTO mod_cat_media_link_links (entity_type, entity_id, media_id, link_type, sort_order, is_primary, created_at, updated_at)
                 VALUES (:entity_type, :entity_id, :media_id, :link_type, :sort_order, :is_primary, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
            );

            foreach ($links as $link) {
                $insert->execute([
                    'entity_type' => $entityType,
                    'entity_id' => $entityId,
                    'media_id' => (int) ($link['media_id'] ?? 0),
                    'link_type' => (string) ($link['link_type'] ?? 'gallery'),
                    'sort_order' => (int) ($link['sort_order'] ?? 0),
                    'is_primary' => !empty($link['is_primary']) ? 1 : 0,
                ]);
            }

            $this->pdo->commit();
            return ['ok' => true, 'message' => 'Liens média synchronisés.'];
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ['ok' => false, 'message' => 'Échec sync média: ' . $e->getMessage()];
        }
    }

    public function entityLinks(string $entityType, int $entityId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT l.*, a.public_url, a.media_type, a.title, a.alt_text
             FROM mod_cat_media_link_links l
             LEFT JOIN mod_cat_media_link_assets a ON a.id = l.media_id
             WHERE l.entity_type = :entity_type AND l.entity_id = :entity_id
             ORDER BY l.sort_order ASC, l.id ASC'
        );
        $stmt->execute(['entity_type' => $entityType, 'entity_id' => $entityId]);
        return (array) $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function scalar(string $sql): int
    {
        $value = $this->pdo->query($sql);
        if (!$value) {
            return 0;
        }
        return (int) $value->fetchColumn();
    }
}
