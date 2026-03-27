<?php

namespace Modules\Users\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Services\RbacPermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Logger\Services\SystemLogService;

class RoleController extends Controller
{
    public function index(): View
    {
        return view()->file(base_path('modules/Users/Views/roles.blade.php'), [
            'currentPage' => 'roles',
            'roles' => Role::query()
                ->withCount('users')
                ->orderBy('priority')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view()->file(base_path('modules/Users/Views/roles-create.blade.php'), [
            'currentPage' => 'roles',
            'allPermissions' => $this->allPermissions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'         => ['required', 'string', 'max:100', 'alpha_dash', 'unique:roles,name'],
            'display_name' => ['nullable', 'string', 'max:255'],
            'description'  => ['nullable', 'string', 'max:1000'],
            'permissions'  => ['nullable', 'array'],
            'permissions.*'=> ['string', 'max:150'],
            'priority'     => ['nullable', 'integer', 'min:0', 'max:1000'],
            'is_active'    => ['nullable', 'boolean'],
        ]);

        $validated['is_system']   = false;
        $validated['permissions'] = $validated['permissions'] ?? [];
        $validated['priority']    = (int) ($validated['priority'] ?? 0);
        $validated['is_active']   = $request->boolean('is_active', true);

        /** @var Role $role */
        $role = Role::query()->create($validated);

        $this->logAudit('role.created', 'Role cree', [
            'role'        => $role->name,
            'priority'    => $role->priority,
            'permissions' => count($role->permissions ?? []),
        ]);

        return redirect()
            ->route('admin.roles.manage')
            ->with('status', 'Role "' . ($role->display_name ?: $role->name) . '" cree avec succes.');
    }

    public function edit(Role $role): View
    {
        return view()->file(base_path('modules/Users/Views/roles-edit.blade.php'), [
            'currentPage'    => 'roles',
            'role'           => $role,
            'allPermissions' => $this->allPermissions(),
        ]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $validated = $request->validate([
            'display_name' => ['nullable', 'string', 'max:255'],
            'description'  => ['nullable', 'string', 'max:1000'],
            'permissions'  => ['nullable', 'array'],
            'permissions.*'=> ['string', 'max:150'],
            'priority'     => ['nullable', 'integer', 'min:0', 'max:1000'],
            'is_active'    => ['nullable', 'boolean'],
        ]);

        $role->display_name = $validated['display_name'] ?? $role->display_name;
        $role->description  = $validated['description'] ?? $role->description;
        $role->priority     = (int) ($validated['priority'] ?? $role->priority);
        $role->is_active    = $request->boolean('is_active');

        // Super-admin role keeps wildcard permissions; other roles freely editable
        if (!($role->is_system && in_array('*', $role->permissions ?? [], true))) {
            $role->permissions = $validated['permissions'] ?? [];
        }

        $role->save();

        $this->logAudit('role.updated', 'Role modifie', [
            'role'        => $role->name,
            'permissions' => count($role->permissions ?? []),
        ]);

        return redirect()
            ->route('admin.roles.manage')
            ->with('status', 'Role "' . ($role->display_name ?: $role->name) . '" mis a jour.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        if ($role->is_system) {
            return redirect()
                ->route('admin.roles.manage')
                ->with('error', 'Le role "' . $role->name . '" est un role systeme et ne peut pas etre supprime.');
        }

        $usersCount = $role->users()->count();
        if ($usersCount > 0) {
            return redirect()
                ->route('admin.roles.manage')
                ->with('error', 'Impossible de supprimer "' . $role->name . '": ' . $usersCount . ' utilisateur(s) ont ce role.');
        }

        $name = $role->display_name ?: $role->name;
        $slug = $role->name;
        $role->delete();

        $this->logAudit('role.deleted', 'Role supprime', ['role' => $slug]);

        return redirect()
            ->route('admin.roles.manage')
            ->with('status', 'Role "' . $name . '" supprime.');
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function allPermissions(): array
    {
        $actions = (array) config('catmin.rbac.actions', ['menu', 'list', 'create', 'edit', 'delete', 'config']);
        $modules = [
            'articles', 'blocks', 'cache', 'cron',
            'logger', 'mailer', 'media', 'menus',
            'pages', 'queue', 'seo', 'settings',
            'shop', 'users', 'webhooks',
        ];

        $permissions = [];
        foreach ($modules as $module) {
            $permissions[$module] = array_map(
                fn (string $action) => RbacPermissionService::modulePermission($module, $action),
                $actions
            );
        }

        return $permissions;
    }

    /**
     * @param array<string, mixed> $context
     */
    private function logAudit(string $event, string $message, array $context = []): void
    {
        try {
            app(SystemLogService::class)->logAudit(
                $event,
                $message,
                $context,
                'info',
                (string) session('catmin_admin_username', '')
            );
        } catch (\Throwable) {
        }
    }
}
