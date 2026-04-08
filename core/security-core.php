<?php

declare(strict_types=1);

use Core\http\Request;
use Core\security\SecurityManager;

require_once CATMIN_CORE . '/security/SecurityManager.php';

final class CoreSecurityCore
{
    public function manager(Request $request, string $area): SecurityManager
    {
        return new SecurityManager($request, $area);
    }
}

