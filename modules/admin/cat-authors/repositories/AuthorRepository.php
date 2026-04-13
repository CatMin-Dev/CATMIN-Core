<?php

declare(strict_types=1);

namespace Modules\CatAuthors\repositories;

use Core\database\ConnectionManager;
use PDO;

final class AuthorRepository
{
    private PDO $pdo;

    private const TABLE_PROFILES = 'mod_cat_author_profiles';
    private const TABLE_LINKS    = 'mod_cat_author_links';
    private const TABLE_ROLES    = 'mod_cat_author_roles';

    public function __construct()
    {
        $this->pdo = (new ConnectionManager())->connection();
    }

    // -------------------------------------------------------------------------
    // Profiles
    // -------------------------------------------------------------------------

    public function allProfiles(): array
    {
        $sql = 'SELECT p.*, u.username, u.email
                FROM ' . self::TABLE_PROFILES . ' p
                LEFT JOIN admin_users u ON u.id = p.user_id
                ORDER BY p.display_name ASC';
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function findProfile(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT p.*, u.username, u.email
             FROM ' . self::TABLE_PROFILES . ' p
             LEFT JOIN admin_users u ON u.id = p.user_id
             WHERE p.id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row !== false ? $row : null;
    }

    public function findProfileBySlug(string $slug): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM ' . self::TABLE_PROFILES . ' WHERE slug = :slug LIMIT 1'
        );
        $stmt->execute(['slug' => $slug]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row !== false ? $row : null;
    }

    public function findProfileByUserId(int $userId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM ' . self::TABLE_PROFILES . ' WHERE user_id = :uid LIMIT 1'
        );
        $stmt->execute(['uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row !== false ? $row : null;
    }

    public function slugExists(string $slug, int $excludeId = 0): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT id FROM ' . self::TABLE_PROFILES . ' WHERE slug = :slug AND id != :eid LIMIT 1'
        );
        $stmt->execute(['slug' => $slug, 'eid' => $excludeId]);
        return $stmt->fetchColumn() !== false;
    }

    public function insertProfile(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO ' . self::TABLE_PROFILES .
            ' (user_id, display_name, slug, bio, avatar_media_id, website_url, socials_json, visibility, created_at)
             VALUES (:user_id, :display_name, :slug, :bio, :avatar_media_id, :website_url, :socials_json, :visibility, CURRENT_TIMESTAMP)'
        );
        $stmt->execute([
            'user_id'        => $data['user_id'] ?? null,
            'display_name'   => $data['display_name'],
            'slug'           => $data['slug'],
            'bio'            => $data['bio'] ?? null,
            'avatar_media_id'=> $data['avatar_media_id'] ?? null,
            'website_url'    => $data['website_url'] ?? null,
            'socials_json'   => $data['socials_json'] ?? null,
            'visibility'     => $data['visibility'] ?? 'public',
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function updateProfile(int $id, array $data): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE ' . self::TABLE_PROFILES . '
             SET user_id = :user_id, display_name = :display_name, slug = :slug,
                 bio = :bio, avatar_media_id = :avatar_media_id, website_url = :website_url,
                 socials_json = :socials_json, visibility = :visibility, updated_at = CURRENT_TIMESTAMP
             WHERE id = :id'
        );
        $stmt->execute([
            'id'             => $id,
            'user_id'        => $data['user_id'] ?? null,
            'display_name'   => $data['display_name'],
            'slug'           => $data['slug'],
            'bio'            => $data['bio'] ?? null,
            'avatar_media_id'=> $data['avatar_media_id'] ?? null,
            'website_url'    => $data['website_url'] ?? null,
            'socials_json'   => $data['socials_json'] ?? null,
            'visibility'     => $data['visibility'] ?? 'public',
        ]);
    }

    public function deleteProfile(int $id): void
    {
        $this->pdo->prepare('DELETE FROM ' . self::TABLE_PROFILES . ' WHERE id = :id')
            ->execute(['id' => $id]);
    }

    public function countProfiles(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM ' . self::TABLE_PROFILES)->fetchColumn();
    }

    // -------------------------------------------------------------------------
    // Links
    // -------------------------------------------------------------------------

    public function entityAuthorId(string $entityType, int $entityId): ?int
    {
        $stmt = $this->pdo->prepare(
            'SELECT author_profile_id FROM ' . self::TABLE_LINKS .
            ' WHERE entity_type = :et AND entity_id = :eid AND is_primary = 1 LIMIT 1'
        );
        $stmt->execute(['et' => $entityType, 'eid' => $entityId]);
        $val = $stmt->fetchColumn();
        return $val !== false ? (int) $val : null;
    }

    public function syncEntityAuthor(string $entityType, int $entityId, ?int $authorProfileId): void
    {
        $del = $this->pdo->prepare(
            'DELETE FROM ' . self::TABLE_LINKS . ' WHERE entity_type = :et AND entity_id = :eid'
        );
        $del->execute(['et' => $entityType, 'eid' => $entityId]);

        if ($authorProfileId !== null && $authorProfileId > 0) {
            $ins = $this->pdo->prepare(
                'INSERT INTO ' . self::TABLE_LINKS .
                ' (author_profile_id, entity_type, entity_id, is_primary, created_at)
                 VALUES (:pid, :et, :eid, 1, CURRENT_TIMESTAMP)'
            );
            $ins->execute(['pid' => $authorProfileId, 'et' => $entityType, 'eid' => $entityId]);
        }
    }

    // -------------------------------------------------------------------------
    // Roles registry
    // -------------------------------------------------------------------------

    /** Returns all admin_roles with a boolean flag 'is_author_role' */
    public function allRolesWithFlag(): array
    {
        $sql = 'SELECT r.id, r.name, r.slug, r.is_system,
                       CASE WHEN ar.role_id IS NOT NULL THEN 1 ELSE 0 END AS is_author_role,
                       ar.note
                FROM admin_roles r
                LEFT JOIN ' . self::TABLE_ROLES . ' ar ON ar.role_id = r.id
                ORDER BY r.name ASC';
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function registeredRoleIds(): array
    {
        $rows = $this->pdo->query(
            'SELECT role_id FROM ' . self::TABLE_ROLES
        )->fetchAll(PDO::FETCH_COLUMN) ?: [];
        return array_map('intval', $rows);
    }

    public function registerRole(int $roleId, ?string $note): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT OR REPLACE INTO ' . self::TABLE_ROLES .
            ' (role_id, note, created_at) VALUES (:rid, :note, CURRENT_TIMESTAMP)'
        );
        $stmt->execute(['rid' => $roleId, 'note' => $note]);
    }

    public function unregisterRole(int $roleId): void
    {
        $this->pdo->prepare('DELETE FROM ' . self::TABLE_ROLES . ' WHERE role_id = :rid')
            ->execute(['rid' => $roleId]);
    }

    public function syncRegisteredRoles(array $roleIds, array $notes): void
    {
        $this->pdo->exec('DELETE FROM ' . self::TABLE_ROLES);
        $stmt = $this->pdo->prepare(
            'INSERT INTO ' . self::TABLE_ROLES .
            ' (role_id, note, created_at) VALUES (:rid, :note, CURRENT_TIMESTAMP)'
        );
        foreach ($roleIds as $rid) {
            $rid = (int) $rid;
            if ($rid <= 0) {
                continue;
            }
            $stmt->execute(['rid' => $rid, 'note' => $notes[$rid] ?? null]);
        }
    }

    /** Returns all admin_users with a flag indicating they have an author profile */
    public function allAdminUsersWithProfileFlag(): array
    {
        $sql = 'SELECT u.id, u.username, u.email, r.name AS role_name, r.slug AS role_slug,
                       CASE WHEN p.id IS NOT NULL THEN 1 ELSE 0 END AS has_profile,
                       p.id AS profile_id, p.display_name AS profile_display_name
                FROM admin_users u
                LEFT JOIN admin_roles r ON r.id = u.role_id
                LEFT JOIN ' . self::TABLE_PROFILES . ' p ON p.user_id = u.id
                ORDER BY u.username ASC';
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
