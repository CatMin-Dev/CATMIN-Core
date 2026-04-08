<?php

declare(strict_types=1);

namespace Core\security;

use Core\config\Config;

final class CsrfManager
{
    private const SESSION_KEY = 'catmin_csrf_token';
    private const DEFAULT_ADMIN_SESSION = 'CATMIN_ADMIN_SESSID';
    private const DEFAULT_INSTALL_SESSION = 'CATMIN_INSTALL_SESSID';

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

    public function regenerate(): string
    {
        $this->startSession();
        $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        return $_SESSION[self::SESSION_KEY];
    }

    private function startSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $area = defined('CATMIN_AREA') ? (string) CATMIN_AREA : 'front';
        $sessionName = $area === 'install'
            ? (string) Config::get('security.install_session_name', self::DEFAULT_INSTALL_SESSION)
            : (string) Config::get('security.admin_session_name', self::DEFAULT_ADMIN_SESSION);

        session_name($sessionName);
        session_start();
    }
}
