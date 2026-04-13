<?php

declare(strict_types=1);

use Core\database\ConnectionManager;
use Core\database\SchemaBuilder;

return static function (): void {
    $pdo = (new ConnectionManager())->connection();
    $driver = (string) $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $schema = new SchemaBuilder($pdo, $driver);

    $schema->create('mod_cat_media_link_assets', [
        ['name' => 'id', 'type' => 'bigint', 'primary' => true, 'auto_increment' => true],
        ['name' => 'media_type', 'type' => 'string', 'length' => 20],
        ['name' => 'source_type', 'type' => 'string', 'length' => 20, 'default' => 'upload'],
        ['name' => 'storage_path', 'type' => 'string', 'length' => 255, 'nullable' => true],
        ['name' => 'public_url', 'type' => 'string', 'length' => 500, 'nullable' => true],
        ['name' => 'mime_type', 'type' => 'string', 'length' => 120, 'nullable' => true],
        ['name' => 'size_bytes', 'type' => 'bigint', 'default' => 0],
        ['name' => 'width', 'type' => 'integer', 'nullable' => true],
        ['name' => 'height', 'type' => 'integer', 'nullable' => true],
        ['name' => 'duration_seconds', 'type' => 'integer', 'nullable' => true],
        ['name' => 'title', 'type' => 'string', 'length' => 180, 'nullable' => true],
        ['name' => 'alt_text', 'type' => 'string', 'length' => 255, 'nullable' => true],
        ['name' => 'created_at', 'type' => 'datetime', 'default' => 'CURRENT_TIMESTAMP'],
        ['name' => 'updated_at', 'type' => 'datetime', 'nullable' => true],
    ], [
        ['name' => 'ix_mod_cat_media_link_assets_type', 'columns' => ['media_type']],
        ['name' => 'ix_mod_cat_media_link_assets_source', 'columns' => ['source_type']],
    ]);

    $schema->create('mod_cat_media_link_links', [
        ['name' => 'id', 'type' => 'bigint', 'primary' => true, 'auto_increment' => true],
        ['name' => 'entity_type', 'type' => 'string', 'length' => 80],
        ['name' => 'entity_id', 'type' => 'bigint'],
        ['name' => 'media_id', 'type' => 'bigint'],
        ['name' => 'link_type', 'type' => 'string', 'length' => 40, 'default' => 'gallery'],
        ['name' => 'sort_order', 'type' => 'integer', 'default' => 0],
        ['name' => 'is_primary', 'type' => 'boolean', 'default' => false],
        ['name' => 'created_at', 'type' => 'datetime', 'default' => 'CURRENT_TIMESTAMP'],
        ['name' => 'updated_at', 'type' => 'datetime', 'nullable' => true],
    ], [
        ['name' => 'ix_mod_cat_media_link_entity', 'columns' => ['entity_type', 'entity_id']],
        ['name' => 'ix_mod_cat_media_link_media', 'columns' => ['media_id']],
        ['name' => 'ix_mod_cat_media_link_type', 'columns' => ['link_type']],
        ['name' => 'ix_mod_cat_media_link_primary', 'columns' => ['is_primary']],
        ['name' => 'ux_mod_cat_media_link_unique', 'columns' => ['entity_type', 'entity_id', 'media_id', 'link_type'], 'unique' => true],
    ]);
};
