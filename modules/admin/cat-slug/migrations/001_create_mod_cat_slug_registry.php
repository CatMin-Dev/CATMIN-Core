<?php

declare(strict_types=1);

use Core\database\ConnectionManager;
use Core\database\SchemaBuilder;

return static function (): void {
    $pdo = (new ConnectionManager())->connection();
    $driver = (string) $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $schema = new SchemaBuilder($pdo, $driver);

    $schema->create('mod_cat_slug_registry', [
        ['name' => 'id', 'type' => 'bigint', 'primary' => true, 'auto_increment' => true],
        ['name' => 'entity_type', 'type' => 'string', 'length' => 80],
        ['name' => 'entity_id', 'type' => 'bigint'],
        ['name' => 'slug', 'type' => 'string', 'length' => 191],
        ['name' => 'scope_key', 'type' => 'string', 'length' => 120, 'default' => 'global'],
        ['name' => 'is_primary', 'type' => 'boolean', 'default' => true],
        ['name' => 'created_at', 'type' => 'datetime', 'default' => 'CURRENT_TIMESTAMP'],
        ['name' => 'updated_at', 'type' => 'datetime', 'nullable' => true],
    ], [
        ['name' => 'ux_mod_cat_slug_scope_slug', 'columns' => ['scope_key', 'slug'], 'unique' => true],
        ['name' => 'ux_mod_cat_slug_entity_primary', 'columns' => ['entity_type', 'entity_id', 'is_primary'], 'unique' => true],
        ['name' => 'ix_mod_cat_slug_entity', 'columns' => ['entity_type', 'entity_id']],
    ]);
};
