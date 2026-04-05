<?php

declare(strict_types=1);

use Core\http\Request;
use Core\http\View;
use Core\security\SecurityManager;

$security = new SecurityManager(Request::capture(), 'install');
$installAvailability = $security->installAvailabilityMiddleware();

return [
    [
        'method' => 'GET',
        'path' => '/',
        'handler' => static function (Request $request): \Core\http\Response {
            return View::make('welcome', ['request' => $request], 'install');
        },
        'middleware' => [$installAvailability],
    ],
];
