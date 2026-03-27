<?php

namespace App\Services;

/**
 * VersioningService
 *
 * Shared V1 versioning rules for modules and addons.
 * Format retained: simple semver "major.minor.patch" (e.g. 1.2.0).
 */
class VersioningService
{
    public const DEFAULT_VERSION = '0.1.0';

    public static function isValid(string $version): bool
    {
        return (bool) preg_match('/^\d+\.\d+\.\d+$/', trim($version));
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
