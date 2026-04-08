<?php

declare(strict_types=1);

use Core\http\Request;
use Core\http\Response;
use Core\security\SecurityManager;
use Install\controllers\InstallerController;
use Install\InstallerStateMachine;

$security = new SecurityManager(Request::capture(), 'install');
$installAvailability = $security->installAvailabilityMiddleware();
$csrfCheck = $security->csrfCheckMiddleware();

$routes = [
    [
        'method' => 'GET',
        'path' => '/',
        'handler' => static fn (): Response => (new InstallerController())->root(),
        'middleware' => [$installAvailability],
    ],
    [
        'method' => 'GET',
        'path' => '/step',
        'handler' => static fn (Request $request): Response => (new InstallerController())->showStep($request),
        'middleware' => [$installAvailability],
    ],
];

foreach (InstallerStateMachine::STEPS as $step) {
    $routes[] = [
        'method' => 'GET',
        'path' => '/step/' . $step,
        'handler' => static fn (Request $request): Response => (new InstallerController())->showStep($request),
        'middleware' => [$installAvailability],
    ];
}

$routes[] = [
    'method' => 'POST',
    'path' => '/step',
    'handler' => static fn (Request $request): Response => (new InstallerController())->submitStep($request),
    'middleware' => [$installAvailability, $csrfCheck],
];

$routes[] = [
    'method' => 'POST',
    'path' => '/db-test',
    'handler' => static fn (Request $request): Response => (new InstallerController())->testDatabase($request),
    'middleware' => [$installAvailability, $csrfCheck],
];

$routes[] = [
    'method' => 'POST',
    'path' => '/reset',
    'handler' => static fn (Request $request): Response => (new InstallerController())->reset($request),
    'middleware' => [$installAvailability, $csrfCheck],
];

$routes[] = [
    'method' => 'GET',
    'path' => '/report',
    'handler' => static fn (): Response => (new InstallerController())->showReport(),
    'middleware' => [$installAvailability],
];

$routes[] = [
    'method' => 'GET',
    'path' => '/backup/download',
    'handler' => static fn (Request $request): Response => (new InstallerController())->downloadInitialBackup($request),
    'middleware' => [$installAvailability],
];

return $routes;
