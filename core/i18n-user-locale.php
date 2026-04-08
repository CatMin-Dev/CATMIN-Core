<?php

declare(strict_types=1);

final class CoreI18nUserLocale
{
    private const SESSION_KEY = 'catmin_admin_locale';
    private const COOKIE_KEY = 'catmin_locale';

    public function resolve(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        $profileLocale = strtolower(trim((string) ($_SESSION['catmin_auth_user']['locale'] ?? '')));
        if (in_array($profileLocale, ['fr', 'en'], true)) {
            return $profileLocale;
        }

        $sessionLocale = strtolower(trim((string) ($_SESSION[self::SESSION_KEY] ?? '')));
        if (in_array($sessionLocale, ['fr', 'en'], true)) {
            return $sessionLocale;
        }

        $cookieLocale = strtolower(trim((string) ($_COOKIE[self::COOKIE_KEY] ?? '')));
        if (in_array($cookieLocale, ['fr', 'en'], true)) {
            return $cookieLocale;
        }

        $systemLocale = strtolower(trim((string) env('CATMIN_DEFAULT_LOCALE', 'fr')));
        return in_array($systemLocale, ['fr', 'en'], true) ? $systemLocale : 'fr';
    }

    public function persist(string $locale): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        $_SESSION[self::SESSION_KEY] = $locale;
        setcookie(self::COOKIE_KEY, $locale, [
            'expires' => time() + (86400 * 365),
            'path' => '/',
            'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => false,
            'samesite' => 'Lax',
        ]);
    }
}

