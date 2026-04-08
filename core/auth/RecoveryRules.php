<?php

declare(strict_types=1);

namespace Core\auth;

final class RecoveryRules
{
    public function canUseSelfServiceReset(?array $user): bool
    {
        if (!is_array($user)) {
            return false;
        }

        $roleSlug = strtolower(trim((string) ($user['role_slug'] ?? '')));
        if ($roleSlug === 'super-admin' || $roleSlug === 'superadmin') {
            return false;
        }

        return true;
    }
}

