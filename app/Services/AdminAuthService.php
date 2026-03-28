<?php

namespace App\Services;

use App\Models\AdminUser;
use Illuminate\Support\Facades\Hash;

class AdminAuthService
{
    /**
     * Attempt to authenticate an admin user.
     *
     * @return array{success: bool, user: AdminUser|null, error: string|null}
     */
    public function attempt(string $username, string $password): array
    {
        // Find the user by username or email
        $user = AdminUser::query()
            ->where('username', $username)
            ->orWhere('email', $username)
            ->first();

        if (!$user) {
            return [
                'success' => false,
                'user'    => null,
                'error'   => 'Identifiants invalides.',
            ];
        }

        // Check if account is active
        if (!$user->is_active) {
            return [
                'success' => false,
                'user'    => null,
                'error'   => 'Compte désactivé.',
            ];
        }

        // Check if account is locked
        if ($user->isLocked()) {
            $minutesLeft = (int) ceil($user->locked_until->diffInMinutes(now()));

            return [
                'success' => false,
                'user'    => null,
                'error'   => "Compte verrouillé après trop de tentatives. Réessayez dans {$minutesLeft} minutes.",
            ];
        }

        // Verify password
        if (!$user->verifyPassword($password)) {
            $user->recordFailedLoginAttempt();

            return [
                'success' => false,
                'user'    => null,
                'error'   => 'Identifiants invalides.',
            ];
        }

        // Successful login
        $user->clearFailedLoginAttempts();

        return [
            'success' => true,
            'user'    => $user,
            'error'   => null,
        ];
    }

    /**
     * Get an admin user by ID.
     */
    public function find(int $id): ?AdminUser
    {
        return AdminUser::find($id);
    }

    /**
     * Create a new admin user (internal use, careful with permissions).
     */
    public function create(string $username, string $email, string $password, bool $isSuperAdmin = false): AdminUser
    {
        return AdminUser::create([
            'username'      => $username,
            'email'         => $email,
            'password'      => Hash::make($password),
            'is_super_admin' => $isSuperAdmin,
            'is_active'     => true,
        ]);
    }
}
