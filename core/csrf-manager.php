<?php

declare(strict_types=1);

use Core\security\CsrfManager;

require_once CATMIN_CORE . '/security/CsrfManager.php';

final class CoreCsrfManager
{
    private CsrfManager $csrf;

    public function __construct()
    {
        $this->csrf = new CsrfManager();
    }

    public function token(): string
    {
        return $this->csrf->token();
    }

    public function validate(?string $token): bool
    {
        return $this->csrf->validate($token);
    }
}

