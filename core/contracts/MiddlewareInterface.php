<?php

declare(strict_types=1);

namespace Core\contracts;

use Core\http\Request;
use Core\http\Response;

interface MiddlewareInterface
{
    public function handle(Request $request, callable $next, array $params = []): Response;
}
