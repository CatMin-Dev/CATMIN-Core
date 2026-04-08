<?php

declare(strict_types=1);

final class CoreSecurity
{
    private static bool $initialized = false;

    public static function init(): void
    {
        if (self::$initialized) {
            return;
        }

        CoreEnv::load();
        self::applyHeaders();
        self::sanitizeInput();

        self::$initialized = true;
    }

    private static function applyHeaders(): void
    {
        // Les headers de securite HTTP sont geres centralement
        // via Core\security\SecurityManager + HeaderManager.
    }

    private static function sanitizeInput(): void
    {
        $_GET = self::sanitizeArray($_GET);
        $_POST = self::sanitizeArray($_POST);
        $_COOKIE = self::sanitizeArray($_COOKIE);
    }

    /**
     * @param array<mixed> $input
     * @return array<mixed>
     */
    private static function sanitizeArray(array $input): array
    {
        foreach ($input as $key => $value) {
            if (is_array($value)) {
                $input[$key] = self::sanitizeArray($value);
                continue;
            }

            if (is_string($value)) {
                $input[$key] = trim($value);
            }
        }

        return $input;
    }
}
