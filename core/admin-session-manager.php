<?php

declare(strict_types=1);

use Core\auth\SessionManager;

require_once CATMIN_CORE . '/auth/SessionManager.php';

final class CoreAdminSessionManager
{
    public function __construct(private readonly SessionManager $inner) {}

    public function inner(): SessionManager
    {
        return $this->inner;
    }
}

