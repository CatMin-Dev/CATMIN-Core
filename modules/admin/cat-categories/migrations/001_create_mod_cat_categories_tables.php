<?php

declare(strict_types=1);

use Core\database\ConnectionManager;
use Core\database\SchemaBuilder;

return static function (): void {
    $pdo = (new ConnectionManager())->connection();
    $driver = (string) $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $schema = new SchemaBuilder($pdo, $driver);

    $schema->create('mod_cat_categories_categories', [
        ['name' => 'id', 'type' => 'bigint', 'primary' => true, 'auto_increment' => true],
        ['name' => 'name', 'type' => 'string', 'length' => 140],
        ['name' => 'slug', 'type' => 'string', 'length' => 180],
        ['name' => 'parent_id', 'type' => 'bigint', 'nullable' => true],
        ['name' => 'sort_order', 'type' => 'integer', 'default' => 0],
        ['name' => 'usage_count', 'type' => 'integer', 'default' => 0],
        ['name' => 'created_at', 'type' => 'datetime', 'default' => 'CURRENT_TIMESTAMP'],
        ['name' => 'updated_at', 'type' => 'datetime', 'nullable' => true],
    ], [
        ['name' => 'ux_mod_cat_categories_slug', 'columns' => ['slug'], 'unique' => true],
        ['name' => 'ix_mod_cat_categories_parent', 'columns' => ['parent_id']],
        ['name' => 'ix_mod_cat_categories_sort', 'columns' => ['sort_order']],
    ]);

    $schema->create('mod_cat_categories_links', [
        ['name' => 'id', 'type' => 'bigint', 'primary' => true, 'auto_increment' => true],
        ['name' => 'category_id', 'type' => 'bigint'],
        ['name' => 'entity_type', 'type' => 'string', 'length' => 80],
        ['name' => 'entity_id', 'type' => 'bigint'],
        ['name' => 'created_at', 'type' => 'datetime', 'default' => 'CURRENT_TIMESTAMP'],
    ], [
        ['name' => 'ux_mod_cat_categories_link', 'columns' => ['category_id', 'entity_type', 'entity_id'], 'unique' => true],
        ['name' => 'ix_mod_cat_categories_entity', 'columns' => ['entity_type', 'entity_id']],
        ['name' => 'ix_mod_cat_categories_category', 'columns' => ['category_id']],
    ]);
};
