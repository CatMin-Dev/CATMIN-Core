<?php

namespace Modules\Users\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class UsersAdminService
{
    public function usersForListing(): Collection
    {
        $query = User::query()
            ->with('roles')
            ->orderBy('id');

        if ($this->supportsActivation()) {
            $query->orderByDesc('is_active');
        }

        return $query->get();
    }

    public function rolesForAssignment(): Collection
    {
        return Role::query()
            ->where('is_active', true)
            ->orderBy('priority')
            ->orderBy('name')
            ->get();
    }

    public function supportsActivation(): bool
    {
        return Schema::hasColumn('users', 'is_active');
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function createUser(array $payload): User
    {
        /** @var User $user */
        $user = DB::transaction(function () use ($payload) {
            $user = new User();
            $user->name = (string) $payload['name'];
            $user->email = (string) $payload['email'];
            $user->password = Hash::make((string) $payload['password']);

            if ($this->supportsActivation()) {
                $user->is_active = (bool) ($payload['is_active'] ?? true);
            }

            $user->save();
            $this->syncRoles($user, $payload['roles'] ?? []);

            return $user;
        });

        return $user;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function updateUser(User $user, array $payload): User
    {
        /** @var User $updated */
        $updated = DB::transaction(function () use ($user, $payload) {
            $user->name = (string) $payload['name'];
            $user->email = (string) $payload['email'];

            if (!empty($payload['password'])) {
                $user->password = Hash::make((string) $payload['password']);
            }

            if ($this->supportsActivation() && array_key_exists('is_active', $payload)) {
                $user->is_active = (bool) $payload['is_active'];
            }

            $user->save();
            $this->syncRoles($user, $payload['roles'] ?? []);

            return $user;
        });

        return $updated;
    }

    public function toggleActive(User $user): bool
    {
        if (!$this->supportsActivation()) {
            return false;
        }

        $user->is_active = !(bool) $user->is_active;

        return $user->save();
    }

    /**
     * @param mixed $roles
     */
    protected function syncRoles(User $user, mixed $roles): void
    {
        $normalizedRoleIds = collect((array) $roles)
            ->filter(fn ($roleId): bool => is_numeric($roleId))
            ->map(fn ($roleId): int => (int) $roleId)
            ->values()
            ->all();

        $user->roles()->sync($normalizedRoleIds);
    }
}
