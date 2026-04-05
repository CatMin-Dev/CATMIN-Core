<?php

declare(strict_types=1);

return static function (\PDO $pdo, array $prefixes): void {
    $admin = (string) ($prefixes['admin'] ?? 'admin_');

    $rolesTable = $admin . 'roles';
    $permissionsTable = $admin . 'permissions';
    $pivotTable = $admin . 'role_permissions';

    $roleSlug = 'super-admin';
    $roleName = 'SuperAdmin';

    $stmt = $pdo->prepare('SELECT id FROM ' . $rolesTable . ' WHERE slug = :slug LIMIT 1');
    $stmt->execute(['slug' => $roleSlug]);
    $roleId = $stmt->fetchColumn();

    if ($roleId === false) {
        $insertRole = $pdo->prepare('INSERT INTO ' . $rolesTable . ' (name, slug, is_system, created_at) VALUES (:name, :slug, :is_system, CURRENT_TIMESTAMP)');
        $insertRole->execute([
            'name' => $roleName,
            'slug' => $roleSlug,
            'is_system' => 1,
        ]);
        $roleId = (int) $pdo->lastInsertId();
    } else {
        $roleId = (int) $roleId;
    }

    $permissions = [
        ['name' => 'Access Dashboard', 'slug' => 'admin.dashboard.access', 'description' => 'Access CATMIN admin dashboard'],
        ['name' => 'Manage Users', 'slug' => 'admin.users.manage', 'description' => 'Create/update/delete admin users'],
        ['name' => 'Manage Roles', 'slug' => 'admin.roles.manage', 'description' => 'Manage admin roles and permissions'],
        ['name' => 'Manage Settings', 'slug' => 'core.settings.manage', 'description' => 'Manage core settings'],
        ['name' => 'View Logs', 'slug' => 'core.logs.view', 'description' => 'Read security and system logs'],
    ];

    $insertPermission = $pdo->prepare('INSERT INTO ' . $permissionsTable . ' (name, slug, description, created_at) VALUES (:name, :slug, :description, CURRENT_TIMESTAMP)');
    $findPermission = $pdo->prepare('SELECT id FROM ' . $permissionsTable . ' WHERE slug = :slug LIMIT 1');
    $attachPermission = $pdo->prepare('INSERT INTO ' . $pivotTable . ' (role_id, permission_id) VALUES (:role_id, :permission_id)');
    $findPivot = $pdo->prepare('SELECT id FROM ' . $pivotTable . ' WHERE role_id = :role_id AND permission_id = :permission_id LIMIT 1');

    foreach ($permissions as $permission) {
        $findPermission->execute(['slug' => $permission['slug']]);
        $permissionId = $findPermission->fetchColumn();

        if ($permissionId === false) {
            $insertPermission->execute([
                'name' => $permission['name'],
                'slug' => $permission['slug'],
                'description' => $permission['description'],
            ]);
            $permissionId = (int) $pdo->lastInsertId();
        } else {
            $permissionId = (int) $permissionId;
        }

        $findPivot->execute([
            'role_id' => $roleId,
            'permission_id' => $permissionId,
        ]);

        if ($findPivot->fetchColumn() === false) {
            $attachPermission->execute([
                'role_id' => $roleId,
                'permission_id' => $permissionId,
            ]);
        }
    }
};
