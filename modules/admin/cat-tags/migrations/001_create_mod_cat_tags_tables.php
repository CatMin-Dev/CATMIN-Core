<?php

declare(strict_types=1);

use Core\database\ConnectionManager;
use Core\database\SchemaBuilder;

return static function (): void {
    $pdo = (new ConnectionManager())->connection();
    $driver = (string) $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $schema = new SchemaBuilder($pdo, $driver);

    $schema->create('mod_cat_tags_tags', [
        ['name' => 'id', 'type' => 'bigint', 'primary' => true, 'auto_increment' => true],
        ['name' => 'name', 'type' => 'string', 'length' => 120],
        ['name' => 'slug', 'type' => 'string', 'length' => 160],
        ['name' => 'usage_count', 'type' => 'integer', 'default' => 0],
        ['name' => 'created_at', 'type' => 'datetime', 'default' => 'CURRENT_TIMESTAMP'],
        ['name' => 'updated_at', 'type' => 'datetime', 'nullable' => true],
    ], [
        ['name' => 'ux_mod_cat_tags_slug', 'columns' => ['slug'], 'unique' => true],
        ['name' => 'ix_mod_cat_tags_name', 'columns' => ['name']],
    ]);

    $schema->create('mod_cat_tags_links', [
        ['name' => 'id', 'type' => 'bigint', 'primary' => true, 'auto_increment' => true],
        ['name' => 'tag_id', 'type' => 'bigint'],
        ['name' => 'entity_type', 'type' => 'string', 'length' => 80],
        ['name' => 'entity_id', 'type' => 'bigint'],
        ['name' => 'created_at', 'type' => 'datetime', 'default' => 'CURRENT_TIMESTAMP'],
    ], [
        ['name' => 'ux_mod_cat_tags_link', 'columns' => ['tag_id', 'entity_type', 'entity_id'], 'unique' => true],
        ['name' => 'ix_mod_cat_tags_entity', 'columns' => ['entity_type', 'entity_id']],
        ['name' => 'ix_mod_cat_tags_tag', 'columns' => ['tag_id']],
    ]);
};
