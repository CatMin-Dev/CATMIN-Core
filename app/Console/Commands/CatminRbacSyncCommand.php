<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Services\RbacPermissionService;
use Illuminate\Console\Command;

class CatminRbacSyncCommand extends Command
{
    protected $signature = 'catmin:rbac:sync {--force : Met a jour aussi les roles systeme existants}';

    protected $description = 'Initialise/synchronise les roles RBAC systeme de base pour CATMIN';

    public function handle(): int
    {
        $roles = [
            [
                'name' => 'super-admin',
                'display_name' => 'Super Admin',
                'description' => 'Acces total a l\'administration',
                'permissions' => ['*'],
                'priority' => 100,
                'is_system' => true,
                'is_active' => true,
            ],
            [
                'name' => 'editor',
                'display_name' => 'Editor',
                'description' => 'Edition du contenu sans administration systeme',
                'permissions' => array_merge(
                    RbacPermissionService::defaultModulePermissions('pages'),
                    RbacPermissionService::defaultModulePermissions('articles'),
                    [
                        RbacPermissionService::modulePermission('media', 'menu'),
                        RbacPermissionService::modulePermission('media', 'list'),
                        RbacPermissionService::modulePermission('media', 'create'),
                        RbacPermissionService::modulePermission('media', 'edit'),
                    ]
                ),
                'priority' => 50,
                'is_system' => true,
                'is_active' => true,
            ],
            [
                'name' => 'viewer',
                'display_name' => 'Viewer',
                'description' => 'Lecture seule des modules de contenu',
                'permissions' => [
                    RbacPermissionService::modulePermission('pages', 'menu'),
                    RbacPermissionService::modulePermission('pages', 'list'),
                    RbacPermissionService::modulePermission('articles', 'menu'),
                    RbacPermissionService::modulePermission('articles', 'list'),
                    RbacPermissionService::modulePermission('media', 'menu'),
                    RbacPermissionService::modulePermission('media', 'list'),
                ],
                'priority' => 10,
                'is_system' => true,
                'is_active' => true,
            ],
            [
                'name' => 'addon-manager',
                'display_name' => 'Addon Manager',
                'description' => 'Consultation du registre addons et gestion distribution/activation',
                'permissions' => [
                    'addon.registry.view',
                    'addon.install',
                    'addon.update',
                    'addon.enable',
                    'addon.disable',
                    'addon.remove',
                ],
                'priority' => 60,
                'is_system' => true,
                'is_active' => true,
            ],
        ];

        $force = (bool) $this->option('force');

        foreach ($roles as $roleData) {
            $existing = Role::query()->where('name', $roleData['name'])->first();

            if (!$existing) {
                Role::query()->create($roleData);
                $this->info("Role cree: {$roleData['name']}");
                continue;
            }

            if ($force || (bool) $existing->is_system) {
                $existing->fill($roleData);
                $existing->save();
                $this->line("Role synchronise: {$roleData['name']}");
            } else {
                $this->line("Role conserve: {$roleData['name']}");
            }
        }

        return self::SUCCESS;
    }
}
