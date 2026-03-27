<?php

namespace Modules\Users\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Users\Services\UsersAdminService;

class UserController extends Controller
{
    public function __construct(private readonly UsersAdminService $usersAdminService)
    {
    }

    public function index(): View
    {
        return view()->file(base_path('modules/Users/Views/index.blade.php'), [
            'currentPage' => 'users',
            'users' => $this->usersAdminService->usersForListing(),
            'supportsActivation' => $this->usersAdminService->supportsActivation(),
        ]);
    }

    public function create(): View
    {
        return view()->file(base_path('modules/Users/Views/create.blade.php'), [
            'currentPage' => 'users',
            'roles' => $this->usersAdminService->rolesForAssignment(),
            'supportsActivation' => $this->usersAdminService->supportsActivation(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['integer', 'exists:roles,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        $this->usersAdminService->createUser($validated);

        return redirect()
            ->route('admin.users.manage')
            ->with('status', 'Utilisateur cree avec succes.');
    }

    public function edit(User $user): View
    {
        $user->load('roles');

        return view()->file(base_path('modules/Users/Views/edit.blade.php'), [
            'currentPage' => 'users',
            'user' => $user,
            'roles' => $this->usersAdminService->rolesForAssignment(),
            'supportsActivation' => $this->usersAdminService->supportsActivation(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['integer', 'exists:roles,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if (!$request->filled('password')) {
            unset($validated['password']);
        }

        $validated['is_active'] = $request->boolean('is_active');

        $this->usersAdminService->updateUser($user, $validated);

        return redirect()
            ->route('admin.users.manage')
            ->with('status', 'Utilisateur mis a jour.');
    }

    public function toggleActive(User $user): RedirectResponse
    {
        $updated = $this->usersAdminService->toggleActive($user);

        if (!$updated) {
            return redirect()
                ->route('admin.users.manage')
                ->with('error', 'Activation indisponible: colonne users.is_active absente.');
        }

        return redirect()
            ->route('admin.users.manage')
            ->with('status', 'Statut utilisateur mis a jour.');
    }
}
