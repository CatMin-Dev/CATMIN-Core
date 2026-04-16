<?php

declare(strict_types=1);

use Core\database\SchemaBuilder;

return static function (SchemaBuilder $schema, array $prefixes): void {
    $schema->create('core_trusted_devices', [
        ['name' => 'id', 'type' => 'bigint', 'primary' => true, 'auto_increment' => true],
        ['name' => 'user_id', 'type' => 'bigint', 'unsigned' => true],
        ['name' => 'fingerprint_hash', 'type' => 'string', 'length' => 128],
        ['name' => 'device_label', 'type' => 'string', 'length' => 190, 'default' => ''],
        ['name' => 'issued_at', 'type' => 'datetime', 'default' => 'CURRENT_TIMESTAMP'],
        ['name' => 'last_seen_at', 'type' => 'datetime', 'nullable' => true],
        ['name' => 'revoked_at', 'type' => 'datetime', 'nullable' => true],
        ['name' => 'ip_last', 'type' => 'string', 'length' => 64, 'nullable' => true],
        ['name' => 'user_agent_last', 'type' => 'string', 'length' => 255, 'nullable' => true],
        ['name' => 'created_at', 'type' => 'datetime', 'default' => 'CURRENT_TIMESTAMP'],
        ['name' => 'updated_at', 'type' => 'datetime', 'nullable' => true],
    ], [
        ['name' => 'ux_core_trusted_devices_user_hash', 'columns' => ['user_id', 'fingerprint_hash'], 'unique' => true],
        ['name' => 'ix_core_trusted_devices_user_id', 'columns' => ['user_id']],
        ['name' => 'ix_core_trusted_devices_revoked_at', 'columns' => ['revoked_at']],
    ]);
};