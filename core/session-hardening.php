<?php

declare(strict_types=1);

final class CoreSessionHardening
{
    public static function configure(bool $secureCookie, int $lifetime): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_set_cookie_params([
            'lifetime' => max(60, $lifetime),
            'path' => '/',
            'secure' => $secureCookie,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
}

