<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Admin extends Model
{
    /**
     * Fillable attributes for mass assignment
     *
     * @var array<string>
     */
    protected $fillable = [
        'username',
        'email',
        'password',
        'first_name',
        'last_name',
        'role',
        'permissions',
        'is_active',
    ];

    /**
     * Attributes that should be hidden from arrays
     *
     * @var array<string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * Casting attributes
     *
     * @var array<string, string>
     */
    protected $casts = [
        'password' => 'hashed',
        'permissions' => 'json',
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get full name of admin
     *
     * @return string
     */
    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}") ?: $this->username;
    }

    /**
     * Check if admin has specific permission
     *
     * @param string $permission
     * @return bool
     */
    public function hasPermission(string $permission): bool
    {
        // Admin role has all permissions
        if ($this->role === 'admin') {
            return true;
        }

        // Check explicit permissions
        return in_array($permission, $this->permissions ?? []);
    }

    /**
     * Update last login tracking
     *
     * @param string|null $ip
     * @return self
     */
    public function recordLogin(?string $ip = null): self
    {
        return tap($this)->update([
            'last_login_at' => now(),
            'last_login_ip' => $ip ?? request()->ip(),
        ]);
    }
}
