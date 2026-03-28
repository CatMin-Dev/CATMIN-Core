<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Hash;

/**
 * @property int    $id
 * @property string $username
 * @property string $email
 * @property string $password
 * @property string|null $first_name
 * @property string|null $last_name
 * @property bool   $is_active
 * @property bool   $is_super_admin
 * @property \Carbon\Carbon|null $last_login_at
 * @property int    $failed_login_attempts
 * @property \Carbon\Carbon|null $locked_until
 * @property array|null $metadata
 */
class AdminUser extends Model
{
    use SoftDeletes;

    protected $table = 'admin_users';

    protected $fillable = [
        'username',
        'email',
        'password',
        'first_name',
        'last_name',
        'is_active',
        'is_super_admin',
        'two_factor_enabled',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'metadata',
    ];

    protected $hidden = ['password'];

    protected $casts = [
        'metadata' => 'array',
        'is_active' => 'boolean',
        'is_super_admin' => 'boolean',
        'two_factor_enabled' => 'boolean',
        'two_factor_recovery_codes' => 'array',
        'last_login_at' => 'datetime',
        'locked_until' => 'datetime',
    ];

    /**
     * Check if the admin account is locked (due to failed login attempts).
     */
    public function isLocked(): bool
    {
        if (!$this->locked_until) {
            return false;
        }

        return now()->lessThan($this->locked_until);
    }

    /**
     * Verify password against hashed password.
     */
    public function verifyPassword(string $password): bool
    {
        return Hash::check($password, $this->password);
    }

    /**
     * Record a failed login attempt.
     */
    public function recordFailedLoginAttempt(): void
    {
        $this->failed_login_attempts++;

        // Lock account after 5 failed attempts for 15 minutes
        if ($this->failed_login_attempts >= 5) {
            $this->locked_until = now()->addMinutes(15);
        }

        $this->save();
    }

    /**
     * Clear failed login attempts (after successful login).
     */
    public function clearFailedLoginAttempts(): void
    {
        $this->failed_login_attempts = 0;
        $this->locked_until = null;
        $this->last_login_at = now();
        $this->save();
    }

    /**
     * Get full name (or username if no name set).
     */
    public function getDisplayName(): string
    {
        if ($this->first_name || $this->last_name) {
            return trim($this->first_name . ' ' . $this->last_name);
        }

        return $this->username;
    }
}
