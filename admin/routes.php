<?php

declare(strict_types=1);

use Admin\controllers\AuthController;
use Core\http\Request;
use Core\http\Response;
use Core\http\View;

$authMiddleware = static function (Request $request, callable $next): Response {
    $controller = new AuthController();

    if (!$controller->requiresAuth()) {
        return Response::html('', 302, ['Location' => $controller->adminBasePath() . '/login']);
    }

    $result = $next($request);

    return $result instanceof Response ? $result : Response::html((string) $result);
};

$reauthMiddleware = static function (Request $request, callable $next): Response {
    $controller = new AuthController();

    if (!$controller->requiresRecentReauth()) {
        return Response::html('', 302, ['Location' => $controller->adminBasePath() . '/reauth']);
    }

    $result = $next($request);

    return $result instanceof Response ? $result : Response::html((string) $result);
};

return [
    [
        'method' => 'GET',
        'path' => '/login',
        'handler' => static fn (): Response => (new AuthController())->showLogin(),
    ],
    [
        'method' => 'POST',
        'path' => '/login',
        'handler' => static fn (Request $request): Response => (new AuthController())->login($request),
    ],
    [
        'method' => 'GET',
        'path' => '/logout',
        'handler' => static fn (): Response => (new AuthController())->logout(),
        'middleware' => [$authMiddleware],
    ],
    [
        'method' => 'GET',
        'path' => '/reauth',
        'handler' => static fn (): Response => (new AuthController())->showReauth(),
        'middleware' => [$authMiddleware],
    ],
    [
        'method' => 'POST',
        'path' => '/reauth',
        'handler' => static fn (Request $request): Response => (new AuthController())->reauth($request),
        'middleware' => [$authMiddleware],
    ],
    [
        'method' => 'GET',
        'path' => '/',
        'handler' => static function (): Response {
            $controller = new AuthController();
            $user = $controller->currentUser();
            return View::make('dashboard', ['user' => $user, 'adminBase' => $controller->adminBasePath()], 'admin');
        },
        'middleware' => [$authMiddleware],
    ],
    [
        'method' => 'POST',
        'path' => '/sensitive-check',
        'handler' => static fn (): Response => Response::json(['ok' => true, 'message' => 'Sensitive action allowed']),
        'middleware' => [$authMiddleware, $reauthMiddleware],
    ],
];
