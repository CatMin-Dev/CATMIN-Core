<?php

declare(strict_types=1);

use Core\auth\AdminAuthenticator;

require_once CATMIN_CORE . '/auth/AdminAuthenticator.php';

final class CoreAdminAuthenticator
{
    public function __construct(private readonly AdminAuthenticator $inner) {}

    public function inner(): AdminAuthenticator
    {
        return $this->inner;
    }
}

