<?php

declare(strict_types=1);

final class CoreInstallLockCheck
{
    public static function isLocked(): bool
    {
        return is_file(CATMIN_STORAGE . '/install.lock') || is_file(CATMIN_STORAGE . '/install/installed.lock');
    }
}

