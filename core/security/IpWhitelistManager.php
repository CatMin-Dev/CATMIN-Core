<?php

declare(strict_types=1);

namespace Core\security;

use Core\config\Config;

final class IpWhitelistManager
{
    public function isEnabled(): bool
    {
        return (bool) Config::get('security.ip_whitelist_enabled', false);
    }

    public function isAllowed(string $ipAddress): bool
    {
        if (!$this->isEnabled()) {
            return true;
        }

        $allowed = Config::get('security.ip_whitelist', []);
        if (!is_array($allowed) || $allowed === []) {
            return false;
        }

        return in_array($ipAddress, array_map('strval', $allowed), true);
    }
}
