<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get all roles assigned to this user
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles')
            ->withPivot(['assigned_at', 'assigned_by_id', 'notes'])
            ->withTimestamps();
    }

    /**
     * Check if user has a specific role
     */
    public function hasRole($roleName): bool
    {
        return $this->roles()->where('name', $roleName)->exists();
    }

    /**
     * Check if user has any of the given roles
     */
    public function hasAnyRole($roles): bool
    {
        $roles = is_array($roles) ? $roles : [$roles];
        return $this->roles()->whereIn('name', $roles)->exists();
    }

    /**
     * Check if user has a specific permission through their roles
     */
    public function hasPermission($permission): bool
    {
        return $this->roles()
            ->with('permissions')
            ->get()
            ->some(fn ($role) => $role->hasPermission($permission));
    }

    /**
     * Assign a role to this user
     */
    public function assignRole($roleId, $assignedById = null, $notes = null)
    {
        return $this->roles()->attach($roleId, [
            'assigned_at' => now(),
            'assigned_by_id' => $assignedById ?? auth()->id(),
            'notes' => $notes,
        ]);
    }

    /**
     * Remove a role from this user
     */
    public function removeRole($roleId)
    {
        return $this->roles()->detach($roleId);
    }
}
