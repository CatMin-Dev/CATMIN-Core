<?php

declare(strict_types=1);

use Core\database\ConnectionManager;
use Core\database\SchemaBuilder;

return static function (): void {
    $pdo = (new ConnectionManager())->connection();
    $driver = (string) $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $schema = new SchemaBuilder($pdo, $driver);

    $schema->create('mod_cat_seo_meta', [
        ['name' => 'id', 'type' => 'bigint', 'primary' => true, 'auto_increment' => true],
        ['name' => 'entity_type', 'type' => 'string', 'length' => 80],
        ['name' => 'entity_id', 'type' => 'bigint'],
        ['name' => 'seo_title', 'type' => 'string', 'length' => 191, 'nullable' => true],
        ['name' => 'meta_description', 'type' => 'text', 'nullable' => true],
        ['name' => 'canonical_url', 'type' => 'string', 'length' => 255, 'nullable' => true],
        ['name' => 'robots_index', 'type' => 'boolean', 'default' => true],
        ['name' => 'robots_follow', 'type' => 'boolean', 'default' => true],
        ['name' => 'og_title', 'type' => 'string', 'length' => 191, 'nullable' => true],
        ['name' => 'og_description', 'type' => 'text', 'nullable' => true],
        ['name' => 'og_image_media_id', 'type' => 'bigint', 'nullable' => true],
        ['name' => 'focus_keyword', 'type' => 'string', 'length' => 120, 'nullable' => true],
        ['name' => 'seo_score', 'type' => 'integer', 'default' => 0],
        ['name' => 'seo_flags_json', 'type' => 'text', 'nullable' => true],
        ['name' => 'created_at', 'type' => 'datetime', 'default' => 'CURRENT_TIMESTAMP'],
        ['name' => 'updated_at', 'type' => 'datetime', 'nullable' => true],
    ], [
        ['name' => 'ux_mod_cat_seo_entity', 'columns' => ['entity_type', 'entity_id'], 'unique' => true],
        ['name' => 'ix_mod_cat_seo_score', 'columns' => ['seo_score']],
        ['name' => 'ix_mod_cat_seo_entity_type', 'columns' => ['entity_type']],
    ]);
};
