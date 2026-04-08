<?php

declare(strict_types=1);

use Core\database\SchemaBuilder;

return static function (SchemaBuilder $schema, array $prefixes): void {
    $core = (string) ($prefixes['core'] ?? 'core_');

    $schema->create($core . 'module_repositories', [
        ['name' => 'id', 'type' => 'bigint', 'primary' => true, 'auto_increment' => true],
        ['name' => 'name', 'type' => 'string', 'length' => 160],
        ['name' => 'slug', 'type' => 'string', 'length' => 120],
        ['name' => 'provider', 'type' => 'string', 'length' => 40, 'default' => 'github'],
        ['name' => 'repo_url', 'type' => 'string', 'length' => 255],
        ['name' => 'api_url', 'type' => 'string', 'length' => 255, 'nullable' => true],
        ['name' => 'index_url', 'type' => 'string', 'length' => 255, 'nullable' => true],
        ['name' => 'branch_or_channel', 'type' => 'string', 'length' => 80, 'default' => 'main'],
        ['name' => 'trust_level', 'type' => 'string', 'length' => 30, 'default' => 'community'],
        ['name' => 'is_official', 'type' => 'boolean', 'default' => false],
        ['name' => 'is_enabled', 'type' => 'boolean', 'default' => true],
        ['name' => 'requires_signature', 'type' => 'boolean', 'default' => false],
        ['name' => 'requires_checksums', 'type' => 'boolean', 'default' => false],
        ['name' => 'requires_manifest_standard', 'type' => 'boolean', 'default' => true],
        ['name' => 'allowed_release_channels', 'type' => 'string', 'length' => 120, 'default' => 'stable,beta,dev'],
        ['name' => 'notes', 'type' => 'text', 'nullable' => true],
        ['name' => 'last_check_at', 'type' => 'datetime', 'nullable' => true],
        ['name' => 'last_check_status', 'type' => 'string', 'length' => 40, 'default' => 'never'],
        ['name' => 'last_check_message', 'type' => 'text', 'nullable' => true],
        ['name' => 'created_at', 'type' => 'datetime', 'default' => 'CURRENT_TIMESTAMP'],
        ['name' => 'updated_at', 'type' => 'datetime', 'nullable' => true],
    ], [
        ['name' => 'ux_core_module_repositories_slug', 'columns' => ['slug'], 'unique' => true],
        ['name' => 'ix_core_module_repositories_enabled', 'columns' => ['is_enabled']],
        ['name' => 'ix_core_module_repositories_trust', 'columns' => ['trust_level']],
    ]);

    $schema->create($core . 'market_policy', [
        ['name' => 'id', 'type' => 'bigint', 'primary' => true, 'auto_increment' => true],
        ['name' => 'policy_key', 'type' => 'string', 'length' => 120],
        ['name' => 'policy_value', 'type' => 'string', 'length' => 120],
        ['name' => 'updated_at', 'type' => 'datetime', 'nullable' => true],
    ], [
        ['name' => 'ux_core_market_policy_key', 'columns' => ['policy_key'], 'unique' => true],
    ]);
};
