<?php

declare(strict_types=1);

use Core\auth\RecoveryRules;

require_once CATMIN_CORE . '/auth/RecoveryRules.php';

final class CoreAdminRecoveryRules
{
    private RecoveryRules $rules;

    public function __construct()
    {
        $this->rules = new RecoveryRules();
    }

    public function canUseSelfServiceReset(?array $user): bool
    {
        return $this->rules->canUseSelfServiceReset($user);
    }
}

