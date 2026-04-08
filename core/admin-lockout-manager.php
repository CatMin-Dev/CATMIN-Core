<?php

declare(strict_types=1);

use Core\auth\LockoutManager;

require_once CATMIN_CORE . '/auth/LockoutManager.php';

final class CoreAdminLockoutManager
{
    public function __construct(private readonly LockoutManager $inner) {}

    public function inner(): LockoutManager
    {
        return $this->inner;
    }
}

