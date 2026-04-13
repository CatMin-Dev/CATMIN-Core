<?php

declare(strict_types=1);

/**
 * RBAC Permission Helpers
 * 
 * Global helper functions for permission checking.
 */

use Catmin\Database\ORM;

if (!function_exists('auth_can')) {
    /**
     * Check if the current user has a specific permission
     * 
     * @param string $permission Permission slug (e.g., 'users.manage', 'articles.write')
     * @return bool True if user has permission, false otherwise
     */
    function auth_can(string $permission): bool
    {
        // Get current session
        $session = session();
        if (!$session) {
            return false;
        }

        $userId = $session->get('admin_user_id');
        if (!$userId) {
            return false;
        }

        try {
            $db = ORM::connection();

            // Get user's role
            $user = $db->table('admin_users')
                ->where('id', $userId)
                ->first(['role_id', 'is_banned']);

            if (!$user || $user['is_banned']) {
                return false;
            }

            // Superadmin always has all permissions
            $role = $db->table('admin_roles')
                ->where('id', $user['role_id'])
                ->first(['slug']);

            if ($role && $role['slug'] === 'superadmin') {
                return true;
            }

            // Check if user's role has this permission
            $hasPermission = $db->table('admin_role_permissions as rp')
                ->join('admin_permissions as p', 'p.id', '=', 'rp.permission_id')
                ->where('rp.role_id', $user['role_id'])
                ->where('p.slug', $permission)
                ->first();

            return (bool) $hasPermission;
        } catch (\Throwable) {
            // Failed queries should not block access - log but return false
            return false;
        }
    }
}

if (!function_exists('auth_can_any')) {
    /**
     * Check if the current user has ANY of the given permissions
     * 
     * @param string|array $permissions Permission slug(s)
     * @return bool True if user has at least one permission
     */
    function auth_can_any($permissions): bool
    {
        $perms = is_array($permissions) ? array_values($permissions) : [$permissions];

        foreach ($perms as $perm) {
            if (is_string($perm) && auth_can($perm)) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('auth_can_all')) {
    /**
     * Check if the current user has ALL of the given permissions
     * 
     * @param string|array $permissions Permission slug(s)
     * @return bool True if user has all permissions
     */
    function auth_can_all($permissions): bool
    {
        $perms = is_array($permissions) ? array_values($permissions) : [$permissions];

        foreach ($perms as $perm) {
            if (!is_string($perm) || !auth_can($perm)) {
                return false;
            }
        }

        return true;
    }
}

if (!function_exists('user_role')) {
    /**
     * Get the current user's role slug
     * 
     * @return string|null Role slug or null if not authenticated
     */
    function user_role(): ?string
    {
        try {
            $session = session();
            if (!$session) {
                return null;
            }

            $userId = $session->get('admin_user_id');
            if (!$userId) {
                return null;
            }

            $db = ORM::connection();
            $user = $db->table('admin_users')
                ->where('id', $userId)
                ->first(['role_id']);

            if (!$user) {
                return null;
            }

            $role = $db->table('admin_roles')
                ->where('id', $user['role_id'])
                ->first(['slug']);

            return $role ? $role['slug'] : null;
        } catch (\Throwable) {
            return null;
        }
    }
}

if (!function_exists('user_is_superadmin')) {
    /**
     * Check if current user is superadmin
     * 
     * @return bool True if superadmin, false otherwise
     */
    function user_is_superadmin(): bool
    {
        return user_role() === 'superadmin';
    }
}
