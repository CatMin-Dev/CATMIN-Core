<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = [
        'name',
        'display_name',
        'description',
        'permissions',
        'priority',
        'is_system',
        'is_active',
    ];

    protected $casts = [
        'permissions' => 'array',
        'is_system' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get all users with this role
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_roles')
            ->withPivot(['assigned_at', 'assigned_by_id', 'notes'])
            ->withTimestamps();
    }

    /**
     * Check if role has a specific permission
     */
    public function hasPermission($permission): bool
    {
        return in_array('*', $this->permissions ?? []) 
            || in_array($permission, $this->permissions ?? []);
    }

    /**
     * Check if role is editable (not system role)
     */
    public function isEditable(): bool
    {
        return !$this->is_system;
    }
}
