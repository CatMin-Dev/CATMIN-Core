<?php

declare(strict_types=1);

use Admin\controllers\AuthController;
use Core\http\Request;
use Core\http\Response;
use Core\http\View;
use Core\security\SecurityManager;

$security = new SecurityManager(Request::capture(), 'admin');
$authRequired = $security->adminAuthRequiredMiddleware();
$csrfCheck = $security->csrfCheckMiddleware();
$recentPassword = $security->recentPasswordRequiredMiddleware();

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
        'middleware' => [$csrfCheck],
    ],
    [
        'method' => 'GET',
        'path' => '/logout',
        'handler' => static fn (): Response => (new AuthController())->logout(),
        'middleware' => [$authRequired],
    ],
    [
        'method' => 'GET',
        'path' => '/reauth',
        'handler' => static fn (): Response => (new AuthController())->showReauth(),
        'middleware' => [$authRequired],
    ],
    [
        'method' => 'POST',
        'path' => '/reauth',
        'handler' => static fn (Request $request): Response => (new AuthController())->reauth($request),
        'middleware' => [$authRequired, $csrfCheck],
    ],
    [
        'method' => 'GET',
        'path' => '/',
        'handler' => static function (): Response {
            $controller = new AuthController();
            $user = $controller->currentUser();
            return View::make('dashboard', ['user' => $user, 'adminBase' => $controller->adminBasePath()], 'admin');
        },
        'middleware' => [$authRequired],
    ],
    [
        'method' => 'POST',
        'path' => '/sensitive-check',
        'handler' => static fn (): Response => Response::json(['ok' => true, 'message' => 'Sensitive action allowed']),
        'middleware' => [$authRequired, $recentPassword, $csrfCheck],
    ],
];
