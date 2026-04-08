<?php

declare(strict_types=1);

use Core\http\Response;
use Core\security\HeaderManager;

require_once CATMIN_CORE . '/security/HeaderManager.php';

final class CoreSecurityHeaders
{
    private HeaderManager $headers;

    public function __construct()
    {
        $this->headers = new HeaderManager();
    }

    public function apply(Response $response, string $csp, bool $noindex, bool $isHttps, bool $sensitive): Response
    {
        return $this->headers->apply($response, $csp, $noindex, $isHttps, $sensitive);
    }
}

