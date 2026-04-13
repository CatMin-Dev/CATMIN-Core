<?php

declare(strict_types=1);

use Core\database\ConnectionManager;
use Core\database\SchemaBuilder;

return static function (): void {
    $pdo = (new ConnectionManager())->connection();
    $driver = (string) $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $schema = new SchemaBuilder($pdo, $driver);

    $profilesTable = 'mod_cat_author_profiles';
    $profilesNewTable = 'mod_cat_author_profiles_v2';
    $linksTable = 'mod_cat_author_links';

    $schema->create($profilesNewTable, [
        ['name' => 'id', 'type' => 'bigint', 'primary' => true, 'auto_increment' => true],
        ['name' => 'user_id', 'type' => 'bigint'],
        ['name' => 'first_name', 'type' => 'string', 'length' => 120],
        ['name' => 'last_name', 'type' => 'string', 'length' => 120],
        ['name' => 'display_name', 'type' => 'string', 'length' => 160],
        ['name' => 'slug', 'type' => 'string', 'length' => 200],
        ['name' => 'bio', 'type' => 'text', 'nullable' => true],
        ['name' => 'avatar_media_id', 'type' => 'bigint', 'nullable' => true],
        ['name' => 'socials_json', 'type' => 'text', 'nullable' => true],
        ['name' => 'visibility', 'type' => 'string', 'length' => 30, 'default' => 'public'],
        ['name' => 'created_at', 'type' => 'datetime', 'default' => 'CURRENT_TIMESTAMP'],
        ['name' => 'updated_at', 'type' => 'datetime', 'nullable' => true],
    ], [
        ['name' => 'ux_mod_cat_author_user_unique', 'columns' => ['user_id'], 'unique' => true],
        ['name' => 'ux_mod_cat_author_slug', 'columns' => ['slug'], 'unique' => true],
        ['name' => 'ix_mod_cat_author_vis', 'columns' => ['visibility']],
    ]);

    $legacyRows = [];
    try {
        $legacyRows = $pdo->query('SELECT * FROM ' . $profilesTable . ' WHERE user_id IS NOT NULL ORDER BY id ASC')->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable) {
        $legacyRows = [];
    }

    if ($legacyRows !== []) {
        $insert = $pdo->prepare(
            'INSERT INTO ' . $profilesNewTable . ' (id, user_id, first_name, last_name, display_name, slug, bio, avatar_media_id, socials_json, visibility, created_at, updated_at)
             VALUES (:id, :user_id, :first_name, :last_name, :display_name, :slug, :bio, :avatar_media_id, :socials_json, :visibility, :created_at, :updated_at)'
        );

        foreach ($legacyRows as $row) {
            $nameParts = preg_split('/\s+/', trim((string) ($row['display_name'] ?? '')), 2) ?: [];
            $firstName = trim((string) ($nameParts[0] ?? ''));
            $lastName = trim((string) ($nameParts[1] ?? ''));
            $insert->execute([
                'id' => (int) ($row['id'] ?? 0),
                'user_id' => (int) ($row['user_id'] ?? 0),
                'first_name' => $firstName,
                'last_name' => $lastName,
                'display_name' => (string) ($row['display_name'] ?? ''),
                'slug' => (string) ($row['slug'] ?? ''),
                'bio' => $row['bio'] ?? null,
                'avatar_media_id' => $row['avatar_media_id'] ?? null,
                'socials_json' => $row['socials_json'] ?? null,
                'visibility' => (string) ($row['visibility'] ?? 'public'),
                'created_at' => $row['created_at'] ?? date('Y-m-d H:i:s'),
                'updated_at' => $row['updated_at'] ?? null,
            ]);
        }
    }

    $pdo->exec('DROP TABLE IF EXISTS mod_cat_author_roles');
    $pdo->exec('DROP TABLE IF EXISTS ' . $profilesTable);

    if ($driver === 'mysql') {
        $pdo->exec('RENAME TABLE ' . $profilesNewTable . ' TO ' . $profilesTable);
    } else {
        $pdo->exec('ALTER TABLE ' . $profilesNewTable . ' RENAME TO ' . $profilesTable);
    }

    $schema->create($linksTable, [
        ['name' => 'id', 'type' => 'bigint', 'primary' => true, 'auto_increment' => true],
        ['name' => 'author_profile_id', 'type' => 'bigint'],
        ['name' => 'entity_type', 'type' => 'string', 'length' => 80],
        ['name' => 'entity_id', 'type' => 'bigint'],
        ['name' => 'is_primary', 'type' => 'boolean', 'default' => true],
        ['name' => 'created_at', 'type' => 'datetime', 'default' => 'CURRENT_TIMESTAMP'],
    ], [
        ['name' => 'ux_mod_cat_author_link', 'columns' => ['author_profile_id', 'entity_type', 'entity_id'], 'unique' => true],
        ['name' => 'ix_mod_cat_author_entity', 'columns' => ['entity_type', 'entity_id']],
        ['name' => 'ix_mod_cat_author_profile', 'columns' => ['author_profile_id']],
    ]);
};