<?php

declare(strict_types=1);

use Core\auth\AdminAuthenticator;

require_once CATMIN_CORE . '/auth/AdminAuthenticator.php';
require_once CATMIN_CORE . '/auth/LockoutManager.php';
require_once CATMIN_CORE . '/auth/SessionManager.php';
require_once CATMIN_CORE . '/auth/ReAuthManager.php';
require_once CATMIN_CORE . '/auth/PasswordPolicy.php';
require_once CATMIN_CORE . '/auth/RecoveryRules.php';

final class CoreAuthEngine
{
    private AdminAuthenticator $auth;

    public function __construct(PDO $pdo)
    {
        $this->auth = new AdminAuthenticator($pdo);
    }

    public function authenticator(): AdminAuthenticator
    {
        return $this->auth;
    }
}

