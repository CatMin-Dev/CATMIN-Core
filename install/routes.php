<?php

declare(strict_types=1);

use Core\http\Request;
use Core\http\Response;
use Core\security\SecurityManager;
use Install\controllers\InstallerController;

$security = new SecurityManager(Request::capture(), 'install');
$installAvailability = $security->installAvailabilityMiddleware();
$csrfCheck = $security->csrfCheckMiddleware();

return [
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
    [
        'method' => 'POST',
        'path' => '/step',
        'handler' => static fn (Request $request): Response => (new InstallerController())->submitStep($request),
        'middleware' => [$installAvailability, $csrfCheck],
    ],
    [
        'method' => 'GET',
        'path' => '/report',
        'handler' => static fn (): Response => (new InstallerController())->showReport(),
        'middleware' => [$installAvailability],
    ],
];
