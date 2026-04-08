<?php

declare(strict_types=1);

use Core\auth\PasswordPolicy;

require_once CATMIN_CORE . '/auth/PasswordPolicy.php';

final class CoreAdminPasswordPolicy
{
    private PasswordPolicy $policy;

    public function __construct()
    {
        $this->policy = new PasswordPolicy();
    }

    public function validate(string $password): array
    {
        return $this->policy->validate($password);
    }
}

