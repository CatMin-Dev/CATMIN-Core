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
        if (is_string($allowed)) {
            $allowed = preg_split('/[\s,]+/', trim($allowed)) ?: [];
        }
        if (!is_array($allowed) || $allowed === []) {
            return false;
        }

        $ipAddress = trim($ipAddress);
        if ($ipAddress === '') {
            return false;
        }

        foreach (array_map('strval', $allowed) as $rule) {
            $rule = trim($rule);
            if ($rule === '') {
                continue;
            }
            if ($rule === $ipAddress) {
                return true;
            }
            if (str_contains($rule, '/') && $this->cidrMatch($ipAddress, $rule)) {
                return true;
            }
        }

        return false;
    }

    private function cidrMatch(string $ip, string $cidr): bool
    {
        [$subnet, $bits] = array_pad(explode('/', $cidr, 2), 2, null);
        if ($subnet === null || $bits === null || !is_numeric($bits)) {
            return false;
        }

        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);
        if ($ipLong === false || $subnetLong === false) {
            return false;
        }

        $mask = -1 << (32 - (int) $bits);
        $subnetLong &= $mask;

        return ($ipLong & $mask) === $subnetLong;
    }
}
