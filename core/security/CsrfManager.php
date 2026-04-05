<?php

declare(strict_types=1);

namespace Core\security;

use Core\config\Config;

final class CsrfManager
{
    private const SESSION_KEY = 'catmin_csrf_token';

    public function token(): string
    {
        $this->startSession();

        if (!isset($_SESSION[self::SESSION_KEY]) || !is_string($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::SESSION_KEY];
    }

    public function validate(?string $token): bool
    {
        $this->startSession();

        $sessionToken = $_SESSION[self::SESSION_KEY] ?? null;
        if (!is_string($sessionToken) || !is_string($token) || $token === '') {
            return false;
        }

        return hash_equals($sessionToken, $token);
    }

    private function startSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_name((string) Config::get('security.admin_session_name', 'CATMIN_ADMIN_SESSID'));
        session_start();
    }
}
