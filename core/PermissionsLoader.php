<?php

declare(strict_types=1);

/**
 * Module Permissions Loader
 * 
 * Scans and loads permissions from all enabled modules and registers them
 * in the admin_permissions table.
 */

namespace Core;

use Core\database\ConnectionManager;
use PDO;

class PermissionsLoader
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new ConnectionManager())->connection();
    }

    /**
     * Load permissions from all enabled modules
     */
    public function loadFromModules(): int
    {
        $registry = new modules\ModuleRegistry();
        $enabled = $registry->enabled();
        $loaded = 0;

        foreach ($enabled as $module) {
            $loaded += $this->registerModulePermissions($module['path']);
        }

        return $loaded;
    }

    /**
     * Register permissions from a specific module
     */
    public function registerModulePermissions(string $modulePath): int
    {
        $permFile = $modulePath . '/permissions.php';
        if (!is_file($permFile)) {
            return 0;
        }

        $permissions = require $permFile;
        if (!is_array($permissions) || $permissions === []) {
            return 0;
        }

        $registered = 0;
        foreach ($permissions as $slug => $name) {
            if ($this->registerPermission($slug, $name)) {
                $registered++;
            }
        }

        return $registered;
    }

    /**
     * Register a single permission
     */
    public function registerPermission(string $slug, string $name, string $description = ''): bool
    {
        if (!$slug || !is_string($slug)) {
            return false;
        }

        // Normalize slug
        $slug = trim((string) $slug);
        if (!preg_match('/^[a-z0-9._-]+$/i', $slug)) {
            return false;
        }

        try {
            // Check if already exists
            $stmt = $this->db->prepare('SELECT id FROM admin_permissions WHERE slug = ? LIMIT 1');
            $stmt->execute([$slug]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                // Update name/description if provided
                if ($name) {
                    $stmt = $this->db->prepare('UPDATE admin_permissions SET name = ?, description = ? WHERE slug = ?');
                    $stmt->execute([$name, $description, $slug]);
                }
                return false; // Not newly registered
            }

            // Insert new permission
            $stmt = $this->db->prepare(
                'INSERT INTO admin_permissions (slug, name, description, created_at) VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([$slug, $name ?: $slug, $description, gmdate('Y-m-d H:i:s')]);

            // Assign to superadmin automatically
            $stmt = $this->db->prepare('SELECT id FROM admin_roles WHERE slug = ? LIMIT 1');
            $stmt->execute(['superadmin']);
            $superadmin = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($superadmin) {
                $stmt = $this->db->prepare('SELECT id FROM admin_permissions WHERE slug = ? LIMIT 1');
                $stmt->execute([$slug]);
                $permRow = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($permRow && !$this->roleHasPermission($superadmin['id'], $permRow['id'])) {
                    $stmt = $this->db->prepare(
                        'INSERT INTO admin_role_permissions (role_id, permission_id) VALUES (?, ?)'
                    );
                    $stmt->execute([$superadmin['id'], $permRow['id']]);
                }
            }

            return true; // Newly registered
        } catch (\Throwable $e) {
            // Log error but don't throw - failed registrations shouldn't break boot
            error_log('PermissionsLoader error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if a role already has a permission
     */
    private function roleHasPermission(int $roleId, int $permissionId): bool
    {
        try {
            $stmt = $this->db->prepare(
                'SELECT id FROM admin_role_permissions WHERE role_id = ? AND permission_id = ? LIMIT 1'
            );
            $stmt->execute([$roleId, $permissionId]);
            $exists = $stmt->fetch(PDO::FETCH_ASSOC);

            return (bool) $exists;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Get all registered permissions grouped by module
     */
    public function groupedByModule(): array
    {
        try {
            $stmt = $this->db->query('SELECT * FROM admin_permissions ORDER BY slug');
            $perms = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $grouped = [];
            foreach ($perms as $perm) {
                $parts = array_values(array_filter(explode('.', $perm['slug'])));
                $module = count($parts) > 1 ? array_shift($parts) : 'core';

                if (!isset($grouped[$module])) {
                    $grouped[$module] = [];
                }

                $grouped[$module][] = $perm;
            }

            ksort($grouped);
            return $grouped;
        } catch (\Throwable) {
            return [];
        }
    }
}
