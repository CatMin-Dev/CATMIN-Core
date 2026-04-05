<?php

declare(strict_types=1);

use Core\database\SchemaBuilder;

return static function (SchemaBuilder $schema, array $prefixes): void {
    $admin = (string) ($prefixes['admin'] ?? 'admin_');
    $core = (string) ($prefixes['core'] ?? 'core_');
    $front = (string) ($prefixes['front'] ?? 'front_');

    $schema->create($admin . 'roles', [
        ['name' => 'id', 'type' => 'bigint', 'primary' => true, 'auto_increment' => true],
        ['name' => 'name', 'type' => 'string', 'length' => 120],
        ['name' => 'slug', 'type' => 'string', 'length' => 120],
        ['name' => 'is_system', 'type' => 'boolean', 'default' => true],
        ['name' => 'created_at', 'type' => 'datetime', 'default' => 'CURRENT_TIMESTAMP'],
    ], [
        ['name' => 'ux_admin_roles_slug', 'columns' => ['slug'], 'unique' => true],
    ]);

    $schema->create($admin . 'users', [
        ['name' => 'id', 'type' => 'bigint', 'primary' => true, 'auto_increment' => true],
        ['name' => 'role_id', 'type' => 'bigint', 'unsigned' => true],
        ['name' => 'username', 'type' => 'string', 'length' => 120],
        ['name' => 'email', 'type' => 'string', 'length' => 191],
        ['name' => 'password_hash', 'type' => 'string', 'length' => 255],
        ['name' => 'is_active', 'type' => 'boolean', 'default' => true],
        ['name' => 'last_login_at', 'type' => 'datetime', 'nullable' => true],
        ['name' => 'created_at', 'type' => 'datetime', 'default' => 'CURRENT_TIMESTAMP'],
        ['name' => 'updated_at', 'type' => 'datetime', 'nullable' => true],
    ], [
        ['name' => 'ux_admin_users_username', 'columns' => ['username'], 'unique' => true],
        ['name' => 'ux_admin_users_email', 'columns' => ['email'], 'unique' => true],
        ['name' => 'ix_admin_users_role_id', 'columns' => ['role_id']],
    ]);

    $schema->create($admin . 'permissions', [
        ['name' => 'id', 'type' => 'bigint', 'primary' => true, 'auto_increment' => true],
        ['name' => 'name', 'type' => 'string', 'length' => 140],
        ['name' => 'slug', 'type' => 'string', 'length' => 160],
        ['name' => 'description', 'type' => 'text', 'nullable' => true],
        ['name' => 'created_at', 'type' => 'datetime', 'default' => 'CURRENT_TIMESTAMP'],
    ], [
        ['name' => 'ux_admin_permissions_slug', 'columns' => ['slug'], 'unique' => true],
    ]);

    $schema->create($admin . 'role_permissions', [
        ['name' => 'id', 'type' => 'bigint', 'primary' => true, 'auto_increment' => true],
        ['name' => 'role_id', 'type' => 'bigint', 'unsigned' => true],
        ['name' => 'permission_id', 'type' => 'bigint', 'unsigned' => true],
    ], [
        ['name' => 'ux_admin_role_permissions_pair', 'columns' => ['role_id', 'permission_id'], 'unique' => true],
        ['name' => 'ix_admin_role_permissions_role_id', 'columns' => ['role_id']],
        ['name' => 'ix_admin_role_permissions_permission_id', 'columns' => ['permission_id']],
    ]);

    $schema->create($admin . 'sessions', [
        ['name' => 'id', 'type' => 'bigint', 'primary' => true, 'auto_increment' => true],
        ['name' => 'user_id', 'type' => 'bigint', 'unsigned' => true],
        ['name' => 'session_token', 'type' => 'string', 'length' => 191],
        ['name' => 'ip_address', 'type' => 'string', 'length' => 64],
        ['name' => 'user_agent', 'type' => 'string', 'length' => 255, 'nullable' => true],
        ['name' => 'last_activity_at', 'type' => 'datetime', 'default' => 'CURRENT_TIMESTAMP'],
        ['name' => 'created_at', 'type' => 'datetime', 'default' => 'CURRENT_TIMESTAMP'],
    ], [
        ['name' => 'ux_admin_sessions_token', 'columns' => ['session_token'], 'unique' => true],
        ['name' => 'ix_admin_sessions_user_id', 'columns' => ['user_id']],
    ]);

    $schema->create($admin . 'login_attempts', [
        ['name' => 'id', 'type' => 'bigint', 'primary' => true, 'auto_increment' => true],
        ['name' => 'identifier', 'type' => 'string', 'length' => 191],
        ['name' => 'ip_address', 'type' => 'string', 'length' => 64],
        ['name' => 'success', 'type' => 'boolean', 'default' => false],
        ['name' => 'attempted_at', 'type' => 'datetime', 'default' => 'CURRENT_TIMESTAMP'],
    ], [
        ['name' => 'ix_admin_login_attempts_identifier', 'columns' => ['identifier']],
        ['name' => 'ix_admin_login_attempts_attempted_at', 'columns' => ['attempted_at']],
    ]);

    $schema->create($admin . 'security_events', [
        ['name' => 'id', 'type' => 'bigint', 'primary' => true, 'auto_increment' => true],
        ['name' => 'user_id', 'type' => 'bigint', 'unsigned' => true, 'nullable' => true],
        ['name' => 'event_type', 'type' => 'string', 'length' => 120],
        ['name' => 'severity', 'type' => 'string', 'length' => 40],
        ['name' => 'payload', 'type' => 'json', 'nullable' => true],
        ['name' => 'ip_address', 'type' => 'string', 'length' => 64, 'nullable' => true],
        ['name' => 'created_at', 'type' => 'datetime', 'default' => 'CURRENT_TIMESTAMP'],
    ], [
        ['name' => 'ix_admin_security_events_user_id', 'columns' => ['user_id']],
        ['name' => 'ix_admin_security_events_event_type', 'columns' => ['event_type']],
    ]);

    $schema->create($core . 'settings', [
        ['name' => 'id', 'type' => 'bigint', 'primary' => true, 'auto_increment' => true],
        ['name' => 'category', 'type' => 'string', 'length' => 120],
        ['name' => 'setting_key', 'type' => 'string', 'length' => 160],
        ['name' => 'setting_value', 'type' => 'text', 'nullable' => true],
        ['name' => 'is_public', 'type' => 'boolean', 'default' => false],
        ['name' => 'updated_at', 'type' => 'datetime', 'nullable' => true],
    ], [
        ['name' => 'ux_core_settings_unique', 'columns' => ['category', 'setting_key'], 'unique' => true],
    ]);

    $schema->create($core . 'modules', [
        ['name' => 'id', 'type' => 'bigint', 'primary' => true, 'auto_increment' => true],
        ['name' => 'name', 'type' => 'string', 'length' => 160],
        ['name' => 'slug', 'type' => 'string', 'length' => 120],
        ['name' => 'version', 'type' => 'string', 'length' => 64],
        ['name' => 'status', 'type' => 'string', 'length' => 40, 'default' => 'inactive'],
        ['name' => 'installed_at', 'type' => 'datetime', 'nullable' => true],
        ['name' => 'updated_at', 'type' => 'datetime', 'nullable' => true],
    ], [
        ['name' => 'ux_core_modules_slug', 'columns' => ['slug'], 'unique' => true],
    ]);

    $schema->create($core . 'logs', [
        ['name' => 'id', 'type' => 'bigint', 'primary' => true, 'auto_increment' => true],
        ['name' => 'channel', 'type' => 'string', 'length' => 80],
        ['name' => 'level', 'type' => 'string', 'length' => 40],
        ['name' => 'message', 'type' => 'text'],
        ['name' => 'context', 'type' => 'json', 'nullable' => true],
        ['name' => 'created_at', 'type' => 'datetime', 'default' => 'CURRENT_TIMESTAMP'],
    ], [
        ['name' => 'ix_core_logs_level', 'columns' => ['level']],
        ['name' => 'ix_core_logs_created_at', 'columns' => ['created_at']],
    ]);

    $schema->create($core . 'install', [
        ['name' => 'id', 'type' => 'bigint', 'primary' => true, 'auto_increment' => true],
        ['name' => 'install_uuid', 'type' => 'string', 'length' => 64],
        ['name' => 'instance_uuid', 'type' => 'string', 'length' => 64],
        ['name' => 'primary_domain', 'type' => 'string', 'length' => 191],
        ['name' => 'installed_version', 'type' => 'string', 'length' => 64],
        ['name' => 'consent_tracking', 'type' => 'boolean', 'default' => false],
        ['name' => 'installed_at', 'type' => 'datetime', 'default' => 'CURRENT_TIMESTAMP'],
    ]);

    $schema->create($core . 'backups', [
        ['name' => 'id', 'type' => 'bigint', 'primary' => true, 'auto_increment' => true],
        ['name' => 'backup_type', 'type' => 'string', 'length' => 40],
        ['name' => 'status', 'type' => 'string', 'length' => 40],
        ['name' => 'file_path', 'type' => 'string', 'length' => 255],
        ['name' => 'checksum', 'type' => 'string', 'length' => 191, 'nullable' => true],
        ['name' => 'size_bytes', 'type' => 'bigint', 'default' => 0],
        ['name' => 'created_at', 'type' => 'datetime', 'default' => 'CURRENT_TIMESTAMP'],
    ], [
        ['name' => 'ix_core_backups_status', 'columns' => ['status']],
    ]);

    $schema->create($core . 'notifications', [
        ['name' => 'id', 'type' => 'bigint', 'primary' => true, 'auto_increment' => true],
        ['name' => 'target_type', 'type' => 'string', 'length' => 60],
        ['name' => 'target_id', 'type' => 'bigint', 'nullable' => true],
        ['name' => 'title', 'type' => 'string', 'length' => 191],
        ['name' => 'body', 'type' => 'text', 'nullable' => true],
        ['name' => 'is_read', 'type' => 'boolean', 'default' => false],
        ['name' => 'created_at', 'type' => 'datetime', 'default' => 'CURRENT_TIMESTAMP'],
    ], [
        ['name' => 'ix_core_notifications_target', 'columns' => ['target_type', 'target_id']],
    ]);

    $schema->create($core . 'documents', [
        ['name' => 'id', 'type' => 'bigint', 'primary' => true, 'auto_increment' => true],
        ['name' => 'code', 'type' => 'string', 'length' => 120],
        ['name' => 'title', 'type' => 'string', 'length' => 191],
        ['name' => 'version', 'type' => 'string', 'length' => 64],
        ['name' => 'content', 'type' => 'text'],
        ['name' => 'is_required', 'type' => 'boolean', 'default' => true],
        ['name' => 'published_at', 'type' => 'datetime', 'nullable' => true],
    ], [
        ['name' => 'ux_core_documents_code_version', 'columns' => ['code', 'version'], 'unique' => true],
    ]);

    $schema->create($core . 'document_acceptances', [
        ['name' => 'id', 'type' => 'bigint', 'primary' => true, 'auto_increment' => true],
        ['name' => 'document_id', 'type' => 'bigint', 'unsigned' => true],
        ['name' => 'subject_type', 'type' => 'string', 'length' => 40],
        ['name' => 'subject_id', 'type' => 'bigint'],
        ['name' => 'ip_address', 'type' => 'string', 'length' => 64, 'nullable' => true],
        ['name' => 'accepted_at', 'type' => 'datetime', 'default' => 'CURRENT_TIMESTAMP'],
    ], [
        ['name' => 'ux_core_doc_acceptances_unique', 'columns' => ['document_id', 'subject_type', 'subject_id'], 'unique' => true],
    ]);

    $schema->create($front . 'users', [
        ['name' => 'id', 'type' => 'bigint', 'primary' => true, 'auto_increment' => true],
        ['name' => 'email', 'type' => 'string', 'length' => 191],
        ['name' => 'password_hash', 'type' => 'string', 'length' => 255],
        ['name' => 'is_active', 'type' => 'boolean', 'default' => true],
        ['name' => 'created_at', 'type' => 'datetime', 'default' => 'CURRENT_TIMESTAMP'],
    ], [
        ['name' => 'ux_front_users_email', 'columns' => ['email'], 'unique' => true],
    ]);
};
