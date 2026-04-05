<?php

declare(strict_types=1);

use Core\http\Request;
use Core\http\Response;
use Core\http\View;

$installLockMiddleware = static function (Request $request, callable $next): Response {
    $lockFile = CATMIN_STORAGE . '/install/installed.lock';

    if (is_file($lockFile)) {
        return Response::text('Installer is locked.', 403);
    }

    $result = $next($request);

    if ($result instanceof Response) {
        return $result;
    }

    return Response::html((string) $result);
};

return [
    [
        'method' => 'GET',
        'path' => '/',
        'handler' => static function (Request $request): \Core\http\Response {
            return View::make('welcome', ['request' => $request], 'install');
        },
        'middleware' => [$installLockMiddleware],
    ],
];
