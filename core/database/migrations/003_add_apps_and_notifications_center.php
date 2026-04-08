<?php

declare(strict_types=1);

use Core\database\SchemaBuilder;

return static function (SchemaBuilder $schema, array $prefixes): void {
    $core = (string) ($prefixes['core'] ?? 'core_');

    $schema->create($core . 'apps', [
        ['name' => 'id', 'type' => 'bigint', 'primary' => true, 'auto_increment' => true],
        ['name' => 'label', 'type' => 'string', 'length' => 120],
        ['name' => 'icon', 'type' => 'string', 'length' => 255, 'nullable' => true],
        ['name' => 'url', 'type' => 'string', 'length' => 255],
        ['name' => 'type', 'type' => 'string', 'length' => 20, 'default' => 'external'],
        ['name' => 'target', 'type' => 'string', 'length' => 20, 'default' => '_blank'],
        ['name' => 'is_enabled', 'type' => 'boolean', 'default' => true],
        ['name' => 'sort_order', 'type' => 'integer', 'default' => 100],
        ['name' => 'created_at', 'type' => 'datetime', 'default' => 'CURRENT_TIMESTAMP'],
        ['name' => 'updated_at', 'type' => 'datetime', 'nullable' => true],
    ], [
        ['name' => 'ix_core_apps_enabled', 'columns' => ['is_enabled']],
        ['name' => 'ix_core_apps_sort', 'columns' => ['sort_order']],
    ]);

    $schema->create($core . 'notification_center', [
        ['name' => 'id', 'type' => 'bigint', 'primary' => true, 'auto_increment' => true],
        ['name' => 'title', 'type' => 'string', 'length' => 191],
        ['name' => 'message', 'type' => 'text', 'nullable' => true],
        ['name' => 'type', 'type' => 'string', 'length' => 40, 'default' => 'info'],
        ['name' => 'source', 'type' => 'string', 'length' => 120, 'default' => 'core'],
        ['name' => 'action_url', 'type' => 'string', 'length' => 255, 'nullable' => true],
        ['name' => 'is_read', 'type' => 'boolean', 'default' => false],
        ['name' => 'created_by', 'type' => 'bigint', 'nullable' => true],
        ['name' => 'created_at', 'type' => 'datetime', 'default' => 'CURRENT_TIMESTAMP'],
    ], [
        ['name' => 'ix_core_notification_center_is_read', 'columns' => ['is_read']],
        ['name' => 'ix_core_notification_center_created_at', 'columns' => ['created_at']],
        ['name' => 'ix_core_notification_center_source', 'columns' => ['source']],
    ]);
};

