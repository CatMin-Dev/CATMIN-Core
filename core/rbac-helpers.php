<?php

declare(strict_types=1);

/**
 * RBAC Permission Helpers
 * 
 * Global helper functions for permission checking.
 */

use Core\database\ConnectionManager;

if (!function_exists('catmin_is_superadmin_slug')) {
    function catmin_is_superadmin_slug(?string $roleSlug): bool
    {
        $slug = strtolower(trim((string) $roleSlug));
        return $slug === 'super-admin' || $slug === 'superadmin';
    }
}

if (!function_exists('auth_can')) {
    /**
     * Check if the current user has a specific permission
     * 
     * @param string $permission Permission slug (e.g., 'users.manage', 'articles.write')
     * @return bool True if user has permission, false otherwise
     */
    function auth_can(string $permission): bool
    {
        // Get user ID from session
        $userId = null;
        
        // Try session manager first
        if (function_exists('session') && is_callable('session')) {
            try {
                $sess = session();
                if ($sess && is_object($sess) && method_exists($sess, 'get')) {
                    $userId = $sess->get('admin_user_id');
                }
            } catch (\Throwable) {
                // Fall through to $_SESSION
            }
        }
        
        // Fall back to $_SESSION directly
        if (!$userId && isset($_SESSION['admin_user_id'])) {
            $userId = $_SESSION['admin_user_id'];
        }
        
        if (!$userId) {
            return false;
        }

        try {
            $db = (new ConnectionManager())->connection();

            // Get the user's role from the current admin schema.
            $stmt = $db->prepare('SELECT role_id FROM admin_users WHERE id = ? LIMIT 1');
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                return false;
            }

            // Superadmin always has all permissions
            $stmt = $db->prepare('SELECT slug FROM admin_roles WHERE id = ? LIMIT 1');
            $stmt->execute([$user['role_id']]);
            $role = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($role && catmin_is_superadmin_slug((string) ($role['slug'] ?? ''))) {
                return true;
            }

            // Check if user's role has this permission
            $stmt = $db->prepare(
                'SELECT rp.id FROM admin_role_permissions rp
                 INNER JOIN admin_permissions p ON p.id = rp.permission_id
                 WHERE rp.role_id = ? AND p.slug = ?
                 LIMIT 1'
            );
            $stmt->execute([$user['role_id'], $permission]);
            $hasPermission = $stmt->fetch(PDO::FETCH_ASSOC);

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
            $userId = null;
            
            // Try session manager first
            if (function_exists('session') && is_callable('session')) {
                try {
                    $sess = session();
                    if ($sess && is_object($sess) && method_exists($sess, 'get')) {
                        $userId = $sess->get('admin_user_id');
                    }
                } catch (\Throwable) {
                    // Fall through to $_SESSION
                }
            }
            
            // Fall back to $_SESSION directly
            if (!$userId && isset($_SESSION['admin_user_id'])) {
                $userId = $_SESSION['admin_user_id'];
            }
            
            if (!$userId) {
                return null;
            }

            $db = (new ConnectionManager())->connection();
            $stmt = $db->prepare('SELECT role_id FROM admin_users WHERE id = ? LIMIT 1');
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                return null;
            }

            $stmt = $db->prepare('SELECT slug FROM admin_roles WHERE id = ? LIMIT 1');
            $stmt->execute([$user['role_id']]);
            $role = $stmt->fetch(PDO::FETCH_ASSOC);

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
        return catmin_is_superadmin_slug(user_role());
    }
}
