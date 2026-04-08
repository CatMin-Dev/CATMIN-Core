<?php

declare(strict_types=1);

use Core\auth\ReAuthManager;

require_once CATMIN_CORE . '/auth/ReAuthManager.php';

final class CoreAdminReauthManager
{
    public function __construct(private readonly ReAuthManager $inner) {}

    public function inner(): ReAuthManager
    {
        return $this->inner;
    }
}

