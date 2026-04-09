<?php

declare(strict_types=1);

use Core\database\SchemaBuilder;

return static function (SchemaBuilder $schema, array $prefixes): void {
    $core = (string) ($prefixes['core'] ?? 'core_');

    $schema->create($core . 'queue_jobs', [
        ['name' => 'id', 'type' => 'bigint', 'primary' => true, 'auto_increment' => true],
        ['name' => 'queue', 'type' => 'string', 'length' => 60, 'default' => 'default'],
        ['name' => 'job_type', 'type' => 'string', 'length' => 140],
        ['name' => 'payload', 'type' => 'json'],
        ['name' => 'status', 'type' => 'string', 'length' => 20, 'default' => 'pending'],
        ['name' => 'attempts', 'type' => 'integer', 'default' => 0],
        ['name' => 'max_attempts', 'type' => 'integer', 'default' => 3],
        ['name' => 'available_at', 'type' => 'datetime', 'nullable' => true],
        ['name' => 'reserved_at', 'type' => 'datetime', 'nullable' => true],
        ['name' => 'finished_at', 'type' => 'datetime', 'nullable' => true],
        ['name' => 'last_error', 'type' => 'text', 'nullable' => true],
        ['name' => 'created_at', 'type' => 'datetime', 'default' => 'CURRENT_TIMESTAMP'],
        ['name' => 'updated_at', 'type' => 'datetime', 'nullable' => true],
    ], [
        ['name' => 'ix_core_queue_jobs_queue', 'columns' => ['queue']],
        ['name' => 'ix_core_queue_jobs_status', 'columns' => ['status']],
        ['name' => 'ix_core_queue_jobs_available_at', 'columns' => ['available_at']],
    ]);

    $schema->create($core . 'telemetry_reports', [
        ['name' => 'id', 'type' => 'bigint', 'primary' => true, 'auto_increment' => true],
        ['name' => 'channel', 'type' => 'string', 'length' => 60, 'default' => 'minimal'],
        ['name' => 'payload', 'type' => 'json'],
        ['name' => 'created_at', 'type' => 'datetime', 'default' => 'CURRENT_TIMESTAMP'],
    ], [
        ['name' => 'ix_core_telemetry_reports_channel', 'columns' => ['channel']],
    ]);
};

