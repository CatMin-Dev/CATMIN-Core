<?php

declare(strict_types=1);

use Core\database\ConnectionManager;
use Core\database\SchemaBuilder;

return static function (): void {
    $pdo = (new ConnectionManager())->connection();
    $driver = (string) $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $schema = new SchemaBuilder($pdo, $driver);

    // Profils auteurs — liés aux comptes admin existants (user_id nullable)
    $schema->create('mod_cat_author_profiles', [
        ['name' => 'id',              'type' => 'bigint',  'primary' => true, 'auto_increment' => true],
        ['name' => 'user_id',         'type' => 'bigint',  'nullable' => true],
        ['name' => 'display_name',    'type' => 'string',  'length' => 160],
        ['name' => 'slug',            'type' => 'string',  'length' => 200],
        ['name' => 'bio',             'type' => 'text',    'nullable' => true],
        ['name' => 'avatar_media_id', 'type' => 'bigint',  'nullable' => true],
        ['name' => 'website_url',     'type' => 'string',  'length' => 255, 'nullable' => true],
        ['name' => 'socials_json',    'type' => 'text',    'nullable' => true],
        ['name' => 'visibility',      'type' => 'string',  'length' => 30,  'default' => 'public'],
        ['name' => 'created_at',      'type' => 'datetime','default' => 'CURRENT_TIMESTAMP'],
        ['name' => 'updated_at',      'type' => 'datetime','nullable' => true],
    ], [
        ['name' => 'ux_mod_cat_author_slug',    'columns' => ['slug'],    'unique' => true],
        ['name' => 'ix_mod_cat_author_user',    'columns' => ['user_id']],
        ['name' => 'ix_mod_cat_author_vis',     'columns' => ['visibility']],
    ]);

    // Liaisons entité → profil auteur
    $schema->create('mod_cat_author_links', [
        ['name' => 'id',               'type' => 'bigint',  'primary' => true, 'auto_increment' => true],
        ['name' => 'author_profile_id','type' => 'bigint'],
        ['name' => 'entity_type',      'type' => 'string',  'length' => 80],
        ['name' => 'entity_id',        'type' => 'bigint'],
        ['name' => 'is_primary',       'type' => 'boolean', 'default' => true],
        ['name' => 'created_at',       'type' => 'datetime','default' => 'CURRENT_TIMESTAMP'],
    ], [
        ['name' => 'ux_mod_cat_author_link',   'columns' => ['author_profile_id', 'entity_type', 'entity_id'], 'unique' => true],
        ['name' => 'ix_mod_cat_author_entity', 'columns' => ['entity_type', 'entity_id']],
        ['name' => 'ix_mod_cat_author_profile','columns' => ['author_profile_id']],
    ]);

    // Registre des rôles admin signalés comme "auteur-capables"
    // Aucune création automatique de rôle : simple référence vers admin_roles.id
    $schema->create('mod_cat_author_roles', [
        ['name' => 'id',         'type' => 'bigint',  'primary' => true, 'auto_increment' => true],
        ['name' => 'role_id',    'type' => 'bigint'],
        ['name' => 'note',       'type' => 'string',  'length' => 255, 'nullable' => true],
        ['name' => 'created_at', 'type' => 'datetime','default' => 'CURRENT_TIMESTAMP'],
    ], [
        ['name' => 'ux_mod_cat_author_role_id', 'columns' => ['role_id'], 'unique' => true],
        ['name' => 'ix_mod_cat_author_role',    'columns' => ['role_id']],
    ]);
};
