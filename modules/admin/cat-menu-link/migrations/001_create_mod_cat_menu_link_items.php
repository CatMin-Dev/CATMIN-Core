<?php

declare(strict_types=1);

use Core\database\ConnectionManager;
use Core\database\SchemaBuilder;

return static function (): void {
    $pdo = (new ConnectionManager())->connection();
    $driver = (string) $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $schema = new SchemaBuilder($pdo, $driver);

    $schema->create('mod_cat_menu_link_items', [
        ['name' => 'id', 'type' => 'bigint', 'primary' => true, 'auto_increment' => true],
        ['name' => 'menu_key', 'type' => 'string', 'length' => 120],
        ['name' => 'entity_type', 'type' => 'string', 'length' => 80],
        ['name' => 'entity_id', 'type' => 'bigint'],
        ['name' => 'parent_item_id', 'type' => 'bigint', 'nullable' => true],
        ['name' => 'label_override', 'type' => 'string', 'length' => 180, 'nullable' => true],
        ['name' => 'target_url', 'type' => 'string', 'length' => 500, 'nullable' => true],
        ['name' => 'link_type', 'type' => 'string', 'length' => 40, 'default' => 'entity_link'],
        ['name' => 'sort_order', 'type' => 'integer', 'default' => 0],
        ['name' => 'is_visible', 'type' => 'boolean', 'default' => true],
        ['name' => 'created_at', 'type' => 'datetime', 'default' => 'CURRENT_TIMESTAMP'],
        ['name' => 'updated_at', 'type' => 'datetime', 'nullable' => true],
    ], [
        ['name' => 'ix_mod_cat_menu_link_menu', 'columns' => ['menu_key']],
        ['name' => 'ix_mod_cat_menu_link_entity', 'columns' => ['entity_type', 'entity_id']],
        ['name' => 'ix_mod_cat_menu_link_parent', 'columns' => ['parent_item_id']],
        ['name' => 'ix_mod_cat_menu_link_sort', 'columns' => ['sort_order']],
    ]);
};
