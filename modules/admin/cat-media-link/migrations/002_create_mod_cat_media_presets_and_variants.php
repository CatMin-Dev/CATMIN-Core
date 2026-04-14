<?php

declare(strict_types=1);

use Core\database\ConnectionManager;
use Core\database\SchemaBuilder;

return static function (): void {
    $pdo = (new ConnectionManager())->connection();
    $driver = (string) $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $schema = new SchemaBuilder($pdo, $driver);

    $schema->create('mod_cat_media_presets', [
        ['name' => 'id', 'type' => 'bigint', 'primary' => true, 'auto_increment' => true],
        ['name' => 'preset_key', 'type' => 'string', 'length' => 100],
        ['name' => 'label', 'type' => 'string', 'length' => 140],
        ['name' => 'width', 'type' => 'integer'],
        ['name' => 'height', 'type' => 'integer'],
        ['name' => 'crop_mode', 'type' => 'string', 'length' => 20, 'default' => 'cover'],
        ['name' => 'ratio_locked', 'type' => 'boolean', 'default' => true],
        ['name' => 'allow_manual_override', 'type' => 'boolean', 'default' => true],
        ['name' => 'auto_generate', 'type' => 'boolean', 'default' => true],
        ['name' => 'is_enabled', 'type' => 'boolean', 'default' => true],
        ['name' => 'sort_order', 'type' => 'integer', 'default' => 0],
        ['name' => 'quality', 'type' => 'integer', 'default' => 82],
        ['name' => 'format', 'type' => 'string', 'length' => 16, 'default' => 'jpg'],
        ['name' => 'created_at', 'type' => 'datetime', 'default' => 'CURRENT_TIMESTAMP'],
        ['name' => 'updated_at', 'type' => 'datetime', 'nullable' => true],
    ], [
        ['name' => 'ux_mod_cat_media_presets_key', 'columns' => ['preset_key'], 'unique' => true],
        ['name' => 'ix_mod_cat_media_presets_auto', 'columns' => ['auto_generate']],
        ['name' => 'ix_mod_cat_media_presets_enabled', 'columns' => ['is_enabled']],
    ]);

    $schema->create('mod_cat_media_variants', [
        ['name' => 'id', 'type' => 'bigint', 'primary' => true, 'auto_increment' => true],
        ['name' => 'media_id', 'type' => 'bigint'],
        ['name' => 'preset_key', 'type' => 'string', 'length' => 100],
        ['name' => 'file_path', 'type' => 'string', 'length' => 500],
        ['name' => 'width', 'type' => 'integer'],
        ['name' => 'height', 'type' => 'integer'],
        ['name' => 'crop_data', 'type' => 'text', 'nullable' => true],
        ['name' => 'generated_by', 'type' => 'string', 'length' => 20, 'default' => 'auto'],
        ['name' => 'created_at', 'type' => 'datetime', 'default' => 'CURRENT_TIMESTAMP'],
        ['name' => 'updated_at', 'type' => 'datetime', 'nullable' => true],
    ], [
        ['name' => 'ux_mod_cat_media_variants_media_preset', 'columns' => ['media_id', 'preset_key'], 'unique' => true],
        ['name' => 'ix_mod_cat_media_variants_media', 'columns' => ['media_id']],
        ['name' => 'ix_mod_cat_media_variants_preset', 'columns' => ['preset_key']],
    ]);

    $schema->create('mod_cat_media_settings', [
        ['name' => 'id', 'type' => 'bigint', 'primary' => true, 'auto_increment' => true],
        ['name' => 'setting_key', 'type' => 'string', 'length' => 120],
        ['name' => 'setting_value', 'type' => 'text', 'nullable' => true],
        ['name' => 'created_at', 'type' => 'datetime', 'default' => 'CURRENT_TIMESTAMP'],
        ['name' => 'updated_at', 'type' => 'datetime', 'nullable' => true],
    ], [
        ['name' => 'ux_mod_cat_media_settings_key', 'columns' => ['setting_key'], 'unique' => true],
    ]);
};
