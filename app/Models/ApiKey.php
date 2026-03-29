<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiKey extends Model
{
    protected $fillable = [
        'name',
        'key_hash',
        'scopes',
        'is_active',
        'expires_at',
        'last_used_at',
        'last_used_ip',
        'usage_count',
        'revoked_at',
        'created_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'scopes' => 'array',
            'is_active' => 'boolean',
            'expires_at' => 'datetime',
            'last_used_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function hasScope(string $scope): bool
    {
        $scopes = (array) ($this->scopes ?? []);

        return in_array('*', $scopes, true) || in_array($scope, $scopes, true);
    }
}
