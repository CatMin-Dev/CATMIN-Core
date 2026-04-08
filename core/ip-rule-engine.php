<?php

declare(strict_types=1);

use Core\security\IpWhitelistManager;

require_once CATMIN_CORE . '/security/IpWhitelistManager.php';

final class CoreIpRuleEngine
{
    private IpWhitelistManager $ipWhitelist;

    public function __construct()
    {
        $this->ipWhitelist = new IpWhitelistManager();
    }

    public function isAllowed(string $ip): bool
    {
        return $this->ipWhitelist->isAllowed($ip);
    }
}

