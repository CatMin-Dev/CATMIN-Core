<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;

class RbacPermissionService
{
    /**
     * Permission convention: module.<slug>.<action>
     * Actions: menu, list, create, edit, delete, config
     */
    public static function modulePermission(string $moduleSlug, string $action): string
    {
        return 'module.' . trim(strtolower($moduleSlug)) . '.' . trim(strtolower($action));
    }

    /**
     * @return array<int, string>
     */
    public static function defaultModulePermissions(string $moduleSlug): array
    {
        return [
            self::modulePermission($moduleSlug, 'menu'),
            self::modulePermission($moduleSlug, 'list'),
            self::modulePermission($moduleSlug, 'create'),
            self::modulePermission($moduleSlug, 'edit'),
            self::modulePermission($moduleSlug, 'delete'),
            self::modulePermission($moduleSlug, 'config'),
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function permissionsForUser(User $user): array
    {
        $permissions = [];

        $user->loadMissing('roles');

        foreach ($user->roles as $role) {
            foreach ((array) ($role->permissions ?? []) as $permission) {
                $permission = (string) $permission;
                if ($permission !== '') {
                    $permissions[] = $permission;
                }
            }
        }

        return array_values(array_unique($permissions));
    }

    /**
     * Resolve RBAC context from the admin login username.
     *
     * @return array{roles: array<int, string>, permissions: array<int, string>, source: string}
     */
    public static function resolveContextForUsername(string $username): array
    {
        $expectedAdmin = (string) config('catmin.admin.username', 'admin');

        // Legacy admin account remains super-admin for backward compatibility.
        if ($username === $expectedAdmin) {
            return [
                'roles' => ['super-admin'],
                'permissions' => ['*'],
                'source' => 'legacy-admin-config',
            ];
        }

        $user = User::query()
            ->where('email', $username)
            ->orWhere('name', $username)
            ->first();

        if (!$user) {
            return [
                'roles' => [],
                'permissions' => [],
                'source' => 'none',
            ];
        }

        $user->loadMissing('roles');

        return [
            'roles' => $user->roles->pluck('name')->map(fn ($r) => (string) $r)->values()->all(),
            'permissions' => self::permissionsForUser($user),
            'source' => 'users-module',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function permissionsFromRequest(Request $request): array
    {
        return (array) $request->session()->get('catmin_rbac_permissions', []);
    }

    public static function allows(Request $request, string $permission): bool
    {
        if (!config('catmin.rbac.enabled', true)) {
            return true;
        }

        $permissions = self::permissionsFromRequest($request);

        if (in_array('*', $permissions, true)) {
            return true;
        }

        return in_array($permission, $permissions, true);
    }
}
