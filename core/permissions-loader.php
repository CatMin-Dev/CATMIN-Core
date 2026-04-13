<?php

declare(strict_types=1);

/**
 * Module Permissions Loader
 * 
 * Scans and loads permissions from all enabled modules and registers them
 * in the admin_permissions table.
 */

namespace Catmin\Core;

use Catmin\Database\ORM;

class PermissionsLoader
{
    private readonly \PDO $db;

    public function __construct()
    {
        $this->db = ORM::connection();
    }

    /**
     * Load permissions from all enabled modules
     */
    public function loadFromModules(): int
    {
        $registry = new ModuleRegistry();
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
            $existing = $this->db->table('admin_permissions')
                ->where('slug', $slug)
                ->first();

            if ($existing) {
                // Update name/description if provided
                if ($name) {
                    $this->db->table('admin_permissions')
                        ->where('slug', $slug)
                        ->update([
                            'name' => $name,
                            'description' => $description,
                        ]);
                }
                return false; // Not newly registered
            }

            // Insert new permission
            $this->db->table('admin_permissions')->insert([
                'slug' => $slug,
                'name' => $name ?: $slug,
                'description' => $description,
                'created_at' => gmdate('Y-m-d H:i:s'),
            ]);

            // Assign to superadmin automatically
            $superadmin = $this->db->table('admin_roles')
                ->where('slug', 'superadmin')
                ->first();

            if ($superadmin) {
                $permRow = $this->db->table('admin_permissions')
                    ->where('slug', $slug)
                    ->first();

                if ($permRow && !$this->roleHasPermission($superadmin['id'], $permRow['id'])) {
                    $this->db->table('admin_role_permissions')->insert([
                        'role_id' => $superadmin['id'],
                        'permission_id' => $permRow['id'],
                    ]);
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
            $exists = $this->db->table('admin_role_permissions')
                ->where('role_id', $roleId)
                ->where('permission_id', $permissionId)
                ->first();

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
            $perms = $this->db->table('admin_permissions')
                ->orderBy('slug')
                ->get();

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
