<?php

namespace App\Services;

/**
 * VersioningService
 *
 * Shared versioning rules for modules/addons (A.B.C only)
 * and dashboard global version (Vx-dev).
 */
class VersioningService
{
    public const DEFAULT_VERSION = '0.1.0';

    /**
     * Module/addon convention: A.B.C (no suffix).
     * A = stable production train, B = beta major iteration, C = minor fixes.
     */
    public static function isValid(string $version): bool
    {
        return (bool) preg_match('/^\d+\.\d+\.\d+$/', trim($version));
    }

    /**
    * Dashboard global convention example: V4-dev.
     */
    public static function isDashboardVersionValid(string $version): bool
    {
        return (bool) preg_match('/^V\d+(?:\.5)?-dev$/i', trim($version));
    }

    public static function normalize(?string $version): string
    {
        $value = trim((string) $version);

        return self::isValid($value) ? $value : self::DEFAULT_VERSION;
    }

    /**
     * Compare versions using PHP version_compare semantics.
     * Returns: -1 if $a < $b, 0 if equal, 1 if $a > $b.
     */
    public static function compare(?string $a, ?string $b): int
    {
        $left = self::normalize($a);
        $right = self::normalize($b);

        return version_compare($left, $right);
    }

    /**
     * Determine if moving from installed => current is an upgrade.
     */
    public static function isUpgrade(?string $installed, ?string $current): bool
    {
        return self::compare($installed, $current) < 0;
    }
}
