<?php

namespace Modules\Users\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Services\AddonManager;
use App\Services\ModuleManager;
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

    public function startPreview(Request $request, Role $role): RedirectResponse
    {
        $permissions = (array) ($role->permissions ?? []);

        if (empty($permissions)) {
            return redirect()
                ->route('admin.roles.manage')
                ->with('error', 'Impossible de tester ce role: aucune permission definie.');
        }

        if (!$request->session()->has('catmin_rbac_preview_snapshot')) {
            $request->session()->put('catmin_rbac_preview_snapshot', [
                'roles' => (array) $request->session()->get('catmin_rbac_roles', []),
                'permissions' => (array) $request->session()->get('catmin_rbac_permissions', []),
                'source' => (string) $request->session()->get('catmin_rbac_source', 'unknown'),
            ]);
        }

        $request->session()->put('catmin_rbac_roles', [$role->name]);
        $request->session()->put('catmin_rbac_permissions', $permissions);
        $request->session()->put('catmin_rbac_source', 'role-preview');
        $request->session()->put('catmin_rbac_preview_active', true);
        $request->session()->put('catmin_rbac_preview_role_id', $role->id);
        $request->session()->put('catmin_rbac_preview_role_name', (string) ($role->display_name ?: $role->name));

        $this->logAudit('role.preview.started', 'Mode apercu role active', [
            'role' => $role->name,
            'role_id' => $role->id,
            'permissions' => count($permissions),
        ]);

        return redirect()
            ->route('admin.index')
            ->with('status', 'Mode apercu active pour le role "' . ($role->display_name ?: $role->name) . '".');
    }

    public function stopPreview(Request $request): RedirectResponse
    {
        if (!$request->session()->has('catmin_rbac_preview_snapshot')) {
            $request->session()->forget([
                'catmin_rbac_preview_active',
                'catmin_rbac_preview_role_id',
                'catmin_rbac_preview_role_name',
            ]);

            return redirect()
                ->route('admin.index')
                ->with('status', 'Aucun mode apercu actif.');
        }

        $snapshot = (array) $request->session()->get('catmin_rbac_preview_snapshot', []);

        $request->session()->put('catmin_rbac_roles', (array) ($snapshot['roles'] ?? []));
        $request->session()->put('catmin_rbac_permissions', (array) ($snapshot['permissions'] ?? []));
        $request->session()->put('catmin_rbac_source', (string) ($snapshot['source'] ?? 'unknown'));

        $request->session()->forget([
            'catmin_rbac_preview_active',
            'catmin_rbac_preview_role_id',
            'catmin_rbac_preview_role_name',
            'catmin_rbac_preview_snapshot',
        ]);

        $this->logAudit('role.preview.stopped', 'Mode apercu role desactive');

        return redirect()
            ->route('admin.roles.manage')
            ->with('status', 'Mode apercu desactive. Contexte RBAC precedent restaure.');
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function allPermissions(): array
    {
        $actions = (array) config('catmin.rbac.actions', ['menu', 'list', 'create', 'edit', 'delete', 'config']);

        $modules = ModuleManager::all()
            ->pluck('slug')
            ->map(fn ($slug) => strtolower((string) $slug))
            ->filter(fn ($slug) => $slug !== '')
            ->values();

        $addons = AddonManager::all()
            ->pluck('slug')
            ->map(fn ($slug) => strtolower((string) $slug))
            ->filter(fn ($slug) => $slug !== '')
            ->values();

        $entries = $modules
            ->merge($addons)
            ->unique()
            ->sort()
            ->values();

        $permissions = [];
        foreach ($entries as $module) {
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
