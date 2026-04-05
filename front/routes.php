<?php

declare(strict_types=1);

use Core\http\Request;
use Core\http\View;
use Core\security\SecurityManager;

$security = new SecurityManager(Request::capture(), 'front');
$noindex = $security->noindexFrontMiddleware();

return [
    [
        'method' => 'GET',
        'path' => '/',
        'handler' => static function (Request $request): \Core\http\Response {
            return View::make('home', ['request' => $request], 'front');
        },
        'middleware' => [$noindex],
    ],
];
