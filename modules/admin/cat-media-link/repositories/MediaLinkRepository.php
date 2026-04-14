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
        $this->ensureSchema();
    }

    public function stats(): array
    {
        return [
            'assets' => (int) $this->scalar('SELECT COUNT(*) FROM mod_cat_media_link_assets'),
            'links' => (int) $this->scalar('SELECT COUNT(*) FROM mod_cat_media_link_links'),
            'featured' => (int) $this->scalar("SELECT COUNT(*) FROM mod_cat_media_link_links WHERE link_type = 'featured' OR is_primary = 1"),
            'presets' => (int) $this->scalar('SELECT COUNT(*) FROM mod_cat_media_presets WHERE is_enabled = 1'),
            'variants' => (int) $this->scalar('SELECT COUNT(*) FROM mod_cat_media_variants'),
        ];
    }

    public function listPresets(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM mod_cat_media_presets ORDER BY sort_order ASC, id ASC');
        return $stmt ? (array) $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    public function listAutoPresets(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM mod_cat_media_presets WHERE is_enabled = 1 AND auto_generate = 1 ORDER BY sort_order ASC, id ASC');
        return $stmt ? (array) $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    public function getPresetByKey(string $presetKey): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM mod_cat_media_presets WHERE preset_key = :preset_key LIMIT 1');
        $stmt->execute(['preset_key' => $presetKey]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return is_array($row) ? $row : null;
    }

    public function savePreset(array $payload): array
    {
        $id = (int) ($payload['id'] ?? 0);
        $params = [
            'preset_key' => (string) ($payload['preset_key'] ?? ''),
            'label' => (string) ($payload['label'] ?? ''),
            'width' => max(1, (int) ($payload['width'] ?? 1)),
            'height' => max(1, (int) ($payload['height'] ?? 1)),
            'crop_mode' => (string) ($payload['crop_mode'] ?? 'cover'),
            'ratio_locked' => !empty($payload['ratio_locked']) ? 1 : 0,
            'allow_manual_override' => !empty($payload['allow_manual_override']) ? 1 : 0,
            'auto_generate' => !empty($payload['auto_generate']) ? 1 : 0,
            'quality' => max(1, min(100, (int) ($payload['quality'] ?? 82))),
            'format' => (string) ($payload['format'] ?? 'jpg'),
            'is_enabled' => !empty($payload['is_enabled']) ? 1 : 0,
            'sort_order' => max(0, (int) ($payload['sort_order'] ?? 0)),
        ];

        if ($id > 0) {
            $stmt = $this->pdo->prepare(
                'UPDATE mod_cat_media_presets
                 SET preset_key = :preset_key, label = :label, width = :width, height = :height,
                     crop_mode = :crop_mode, ratio_locked = :ratio_locked, allow_manual_override = :allow_manual_override,
                     auto_generate = :auto_generate, quality = :quality, format = :format,
                     is_enabled = :is_enabled, sort_order = :sort_order, updated_at = CURRENT_TIMESTAMP
                 WHERE id = :id'
            );
            $ok = $stmt->execute($params + ['id' => $id]);
            return ['ok' => $ok, 'id' => $id];
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO mod_cat_media_presets
             (preset_key, label, width, height, crop_mode, ratio_locked, allow_manual_override, auto_generate, quality, format, is_enabled, sort_order, created_at, updated_at)
             VALUES
             (:preset_key, :label, :width, :height, :crop_mode, :ratio_locked, :allow_manual_override, :auto_generate, :quality, :format, :is_enabled, :sort_order, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        );
        $ok = $stmt->execute($params);
        return ['ok' => $ok, 'id' => $ok ? (int) $this->pdo->lastInsertId() : 0];
    }

    public function deletePreset(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }
        $stmt = $this->pdo->prepare('DELETE FROM mod_cat_media_presets WHERE id = :id');
        return (bool) $stmt->execute(['id' => $id]);
    }

    public function listVariantsByMedia(int $mediaId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM mod_cat_media_variants WHERE media_id = :media_id ORDER BY created_at DESC, id DESC');
        $stmt->execute(['media_id' => $mediaId]);
        return (array) $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function upsertVariant(array $payload): array
    {
        $mediaId = (int) ($payload['media_id'] ?? 0);
        $presetKey = (string) ($payload['preset_key'] ?? 'custom');
        if ($mediaId <= 0 || $presetKey === '') {
            return ['ok' => false, 'id' => 0];
        }

        $existing = $this->pdo->prepare('SELECT id FROM mod_cat_media_variants WHERE media_id = :media_id AND preset_key = :preset_key LIMIT 1');
        $existing->execute(['media_id' => $mediaId, 'preset_key' => $presetKey]);
        $existingId = (int) ($existing->fetchColumn() ?: 0);

        $params = [
            'media_id' => $mediaId,
            'preset_key' => $presetKey,
            'file_path' => (string) ($payload['file_path'] ?? ''),
            'width' => max(1, (int) ($payload['width'] ?? 1)),
            'height' => max(1, (int) ($payload['height'] ?? 1)),
            'crop_data' => (string) ($payload['crop_data'] ?? '{}'),
            'generated_by' => (string) ($payload['generated_by'] ?? 'auto'),
        ];

        if ($existingId > 0) {
            $stmt = $this->pdo->prepare(
                'UPDATE mod_cat_media_variants
                 SET file_path = :file_path, width = :width, height = :height, crop_data = :crop_data,
                     generated_by = :generated_by, updated_at = CURRENT_TIMESTAMP
                 WHERE id = :id'
            );
            $ok = $stmt->execute($params + ['id' => $existingId]);
            return ['ok' => $ok, 'id' => $existingId];
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO mod_cat_media_variants
             (media_id, preset_key, file_path, width, height, crop_data, generated_by, created_at, updated_at)
             VALUES
             (:media_id, :preset_key, :file_path, :width, :height, :crop_data, :generated_by, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        );
        $ok = $stmt->execute($params);
        return ['ok' => $ok, 'id' => $ok ? (int) $this->pdo->lastInsertId() : 0];
    }

    public function deleteVariant(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }
        $stmt = $this->pdo->prepare('DELETE FROM mod_cat_media_variants WHERE id = :id');
        return (bool) $stmt->execute(['id' => $id]);
    }

    public function findVariant(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM mod_cat_media_variants WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return is_array($row) ? $row : null;
    }

    public function findAsset(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM mod_cat_media_link_assets WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return is_array($row) ? $row : null;
    }

    public function listVariantsForAssetIds(array $assetIds): array
    {
        $ids = array_values(array_filter(array_map(static fn ($id): int => (int) $id, $assetIds), static fn (int $id): bool => $id > 0));
        if ($ids === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->pdo->prepare('SELECT * FROM mod_cat_media_variants WHERE media_id IN (' . $placeholders . ') ORDER BY created_at DESC, id DESC');
        $stmt->execute($ids);
        return (array) $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getSettings(): array
    {
        $stmt = $this->pdo->query('SELECT setting_key, setting_value FROM mod_cat_media_settings');
        $rows = $stmt ? (array) $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        $settings = [];
        foreach ($rows as $row) {
            $key = (string) ($row['setting_key'] ?? '');
            if ($key === '') {
                continue;
            }
            $settings[$key] = (string) ($row['setting_value'] ?? '');
        }

        return [
            'auto_generate_enabled' => (($settings['auto_generate_enabled'] ?? '1') === '1'),
            'manual_editor_enabled' => (($settings['manual_editor_enabled'] ?? '1') === '1'),
            'default_quality' => max(1, min(100, (int) ($settings['default_quality'] ?? '82'))),
            'allowed_formats' => trim((string) ($settings['allowed_formats'] ?? 'jpg,webp,png')),
            'crop_required' => (($settings['crop_required'] ?? '0') === '1'),
            'fallback_mode' => trim((string) ($settings['fallback_mode'] ?? 'original')) ?: 'original',
        ];
    }

    public function saveSettings(array $settings): bool
    {
        $driver = (string) $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            $stmt = $this->pdo->prepare(
                'INSERT INTO mod_cat_media_settings (setting_key, setting_value, updated_at)
                 VALUES (:setting_key, :setting_value, CURRENT_TIMESTAMP)
                 ON CONFLICT(setting_key) DO UPDATE SET setting_value = excluded.setting_value, updated_at = CURRENT_TIMESTAMP'
            );
        } else {
            $stmt = $this->pdo->prepare(
                'INSERT INTO mod_cat_media_settings (setting_key, setting_value, updated_at)
                 VALUES (:setting_key, :setting_value, CURRENT_TIMESTAMP)
                 ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = CURRENT_TIMESTAMP'
            );
        }

        foreach ($settings as $key => $value) {
            $ok = $stmt->execute([
                'setting_key' => (string) $key,
                'setting_value' => (string) $value,
            ]);
            if (!$ok) {
                return false;
            }
        }
        return true;
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

    private function ensureSchema(): void
    {
        $driver = (string) $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            $this->pdo->exec(
                'CREATE TABLE IF NOT EXISTS mod_cat_media_link_assets ('
                . 'id INTEGER PRIMARY KEY AUTOINCREMENT,'
                . 'media_type VARCHAR(20) NOT NULL,'
                . 'source_type VARCHAR(20) NOT NULL DEFAULT \'upload\','
                . 'storage_path VARCHAR(255) NULL,'
                . 'public_url VARCHAR(500) NULL,'
                . 'mime_type VARCHAR(120) NULL,'
                . 'size_bytes INTEGER NOT NULL DEFAULT 0,'
                . 'width INTEGER NULL,'
                . 'height INTEGER NULL,'
                . 'duration_seconds INTEGER NULL,'
                . 'title VARCHAR(180) NULL,'
                . 'alt_text VARCHAR(255) NULL,'
                . 'created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,'
                . 'updated_at DATETIME NULL'
                . ')'
            );
            $this->pdo->exec('CREATE INDEX IF NOT EXISTS ix_mod_cat_media_link_assets_type ON mod_cat_media_link_assets(media_type)');
            $this->pdo->exec('CREATE INDEX IF NOT EXISTS ix_mod_cat_media_link_assets_source ON mod_cat_media_link_assets(source_type)');

            $this->pdo->exec(
                'CREATE TABLE IF NOT EXISTS mod_cat_media_link_links ('
                . 'id INTEGER PRIMARY KEY AUTOINCREMENT,'
                . 'entity_type VARCHAR(80) NOT NULL,'
                . 'entity_id INTEGER NOT NULL,'
                . 'media_id INTEGER NOT NULL,'
                . 'link_type VARCHAR(40) NOT NULL DEFAULT \'gallery\','
                . 'sort_order INTEGER NOT NULL DEFAULT 0,'
                . 'is_primary INTEGER NOT NULL DEFAULT 0,'
                . 'created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,'
                . 'updated_at DATETIME NULL'
                . ')'
            );
            $this->pdo->exec('CREATE INDEX IF NOT EXISTS ix_mod_cat_media_link_entity ON mod_cat_media_link_links(entity_type, entity_id)');
            $this->pdo->exec('CREATE INDEX IF NOT EXISTS ix_mod_cat_media_link_media ON mod_cat_media_link_links(media_id)');
            $this->pdo->exec('CREATE INDEX IF NOT EXISTS ix_mod_cat_media_link_type ON mod_cat_media_link_links(link_type)');
            $this->pdo->exec('CREATE INDEX IF NOT EXISTS ix_mod_cat_media_link_primary ON mod_cat_media_link_links(is_primary)');
            $this->pdo->exec('CREATE UNIQUE INDEX IF NOT EXISTS ux_mod_cat_media_link_unique ON mod_cat_media_link_links(entity_type, entity_id, media_id, link_type)');

            $this->pdo->exec(
                'CREATE TABLE IF NOT EXISTS mod_cat_media_presets ('
                . 'id INTEGER PRIMARY KEY AUTOINCREMENT,'
                . 'preset_key VARCHAR(80) NOT NULL,'
                . 'label VARCHAR(150) NOT NULL,'
                . 'width INTEGER NOT NULL,'
                . 'height INTEGER NOT NULL,'
                . 'crop_mode VARCHAR(20) NOT NULL DEFAULT \'cover\','
                . 'ratio_locked INTEGER NOT NULL DEFAULT 1,'
                . 'allow_manual_override INTEGER NOT NULL DEFAULT 1,'
                . 'auto_generate INTEGER NOT NULL DEFAULT 1,'
                . 'quality INTEGER NOT NULL DEFAULT 82,'
                . 'format VARCHAR(20) NOT NULL DEFAULT \'jpg\','
                . 'is_enabled INTEGER NOT NULL DEFAULT 1,'
                . 'sort_order INTEGER NOT NULL DEFAULT 0,'
                . 'created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,'
                . 'updated_at DATETIME NULL'
                . ')'
            );
            $this->pdo->exec('CREATE UNIQUE INDEX IF NOT EXISTS ux_mod_cat_media_presets_key ON mod_cat_media_presets(preset_key)');
            $this->pdo->exec('CREATE INDEX IF NOT EXISTS ix_mod_cat_media_presets_auto ON mod_cat_media_presets(auto_generate, is_enabled)');

            $this->pdo->exec(
                'CREATE TABLE IF NOT EXISTS mod_cat_media_variants ('
                . 'id INTEGER PRIMARY KEY AUTOINCREMENT,'
                . 'media_id INTEGER NOT NULL,'
                . 'preset_key VARCHAR(80) NOT NULL,'
                . 'file_path VARCHAR(500) NOT NULL,'
                . 'width INTEGER NOT NULL,'
                . 'height INTEGER NOT NULL,'
                . 'crop_data TEXT NULL,'
                . 'generated_by VARCHAR(20) NOT NULL DEFAULT \'auto\','
                . 'created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,'
                . 'updated_at DATETIME NULL'
                . ')'
            );
            $this->pdo->exec('CREATE UNIQUE INDEX IF NOT EXISTS ux_mod_cat_media_variants_unique ON mod_cat_media_variants(media_id, preset_key)');
            $this->pdo->exec('CREATE INDEX IF NOT EXISTS ix_mod_cat_media_variants_media ON mod_cat_media_variants(media_id)');

            $this->pdo->exec(
                'CREATE TABLE IF NOT EXISTS mod_cat_media_settings ('
                . 'setting_key VARCHAR(120) PRIMARY KEY,'
                . 'setting_value TEXT NULL,'
                . 'updated_at DATETIME NULL'
                . ')'
            );

            $countStmt = $this->pdo->query('SELECT COUNT(*) FROM mod_cat_media_presets');
            $count = (int) ($countStmt ? $countStmt->fetchColumn() : 0);
            if ($count === 0) {
                $this->seedDefaultPresets();
            }
            return;
        }

        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS mod_cat_media_link_assets ('
            . 'id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,'
            . 'media_type VARCHAR(20) NOT NULL,'
            . 'source_type VARCHAR(20) NOT NULL DEFAULT \'upload\','
            . 'storage_path VARCHAR(255) NULL,'
            . 'public_url VARCHAR(500) NULL,'
            . 'mime_type VARCHAR(120) NULL,'
            . 'size_bytes BIGINT UNSIGNED NOT NULL DEFAULT 0,'
            . 'width INT NULL,'
            . 'height INT NULL,'
            . 'duration_seconds INT NULL,'
            . 'title VARCHAR(180) NULL,'
            . 'alt_text VARCHAR(255) NULL,'
            . 'created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,'
            . 'updated_at DATETIME NULL,'
            . 'KEY ix_mod_cat_media_link_assets_type (media_type),'
            . 'KEY ix_mod_cat_media_link_assets_source (source_type)'
            . ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS mod_cat_media_link_links ('
            . 'id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,'
            . 'entity_type VARCHAR(80) NOT NULL,'
            . 'entity_id BIGINT UNSIGNED NOT NULL,'
            . 'media_id BIGINT UNSIGNED NOT NULL,'
            . 'link_type VARCHAR(40) NOT NULL DEFAULT \'gallery\','
            . 'sort_order INT NOT NULL DEFAULT 0,'
            . 'is_primary TINYINT(1) NOT NULL DEFAULT 0,'
            . 'created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,'
            . 'updated_at DATETIME NULL,'
            . 'KEY ix_mod_cat_media_link_entity (entity_type, entity_id),'
            . 'KEY ix_mod_cat_media_link_media (media_id),'
            . 'KEY ix_mod_cat_media_link_type (link_type),'
            . 'KEY ix_mod_cat_media_link_primary (is_primary),'
            . 'UNIQUE KEY ux_mod_cat_media_link_unique (entity_type, entity_id, media_id, link_type)'
            . ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS mod_cat_media_presets ('
            . 'id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,'
            . 'preset_key VARCHAR(80) NOT NULL,'
            . 'label VARCHAR(150) NOT NULL,'
            . 'width INT NOT NULL,'
            . 'height INT NOT NULL,'
            . 'crop_mode VARCHAR(20) NOT NULL DEFAULT \'cover\','
            . 'ratio_locked TINYINT(1) NOT NULL DEFAULT 1,'
            . 'allow_manual_override TINYINT(1) NOT NULL DEFAULT 1,'
            . 'auto_generate TINYINT(1) NOT NULL DEFAULT 1,'
            . 'quality INT NOT NULL DEFAULT 82,'
            . 'format VARCHAR(20) NOT NULL DEFAULT \'jpg\','
            . 'is_enabled TINYINT(1) NOT NULL DEFAULT 1,'
            . 'sort_order INT NOT NULL DEFAULT 0,'
            . 'created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,'
            . 'updated_at DATETIME NULL,'
            . 'UNIQUE KEY ux_mod_cat_media_presets_key (preset_key),'
            . 'KEY ix_mod_cat_media_presets_auto (auto_generate, is_enabled)'
            . ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS mod_cat_media_variants ('
            . 'id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,'
            . 'media_id BIGINT UNSIGNED NOT NULL,'
            . 'preset_key VARCHAR(80) NOT NULL,'
            . 'file_path VARCHAR(500) NOT NULL,'
            . 'width INT NOT NULL,'
            . 'height INT NOT NULL,'
            . 'crop_data TEXT NULL,'
            . 'generated_by VARCHAR(20) NOT NULL DEFAULT \'auto\','
            . 'created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,'
            . 'updated_at DATETIME NULL,'
            . 'UNIQUE KEY ux_mod_cat_media_variants_unique (media_id, preset_key),'
            . 'KEY ix_mod_cat_media_variants_media (media_id)'
            . ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS mod_cat_media_settings ('
            . 'setting_key VARCHAR(120) PRIMARY KEY,'
            . 'setting_value TEXT NULL,'
            . 'updated_at DATETIME NULL'
            . ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        $countStmt = $this->pdo->query('SELECT COUNT(*) FROM mod_cat_media_presets');
        $count = (int) ($countStmt ? $countStmt->fetchColumn() : 0);
        if ($count === 0) {
            $this->seedDefaultPresets();
        }
    }

    private function seedDefaultPresets(): void
    {
        $this->savePreset([
            'preset_key' => 'thumb',
            'label' => 'Thumbnail',
            'width' => 320,
            'height' => 320,
            'crop_mode' => 'cover',
            'ratio_locked' => 1,
            'allow_manual_override' => 1,
            'auto_generate' => 1,
            'quality' => 82,
            'format' => 'jpg',
            'is_enabled' => 1,
            'sort_order' => 10,
        ]);

        $this->savePreset([
            'preset_key' => 'card',
            'label' => 'Card 4:3',
            'width' => 800,
            'height' => 600,
            'crop_mode' => 'cover',
            'ratio_locked' => 1,
            'allow_manual_override' => 1,
            'auto_generate' => 1,
            'quality' => 84,
            'format' => 'webp',
            'is_enabled' => 1,
            'sort_order' => 20,
        ]);

        $this->savePreset([
            'preset_key' => 'hero',
            'label' => 'Hero 16:9',
            'width' => 1600,
            'height' => 900,
            'crop_mode' => 'cover',
            'ratio_locked' => 1,
            'allow_manual_override' => 1,
            'auto_generate' => 0,
            'quality' => 86,
            'format' => 'jpg',
            'is_enabled' => 1,
            'sort_order' => 30,
        ]);

        $this->saveSettings([
            'auto_generate_enabled' => '1',
            'manual_editor_enabled' => '1',
            'default_quality' => '82',
            'allowed_formats' => 'jpg,webp,png',
            'crop_required' => '0',
            'fallback_mode' => 'original',
        ]);
    }
}
