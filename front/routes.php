<?php

declare(strict_types=1);

use Core\http\Request;
use Core\http\View;
use Core\security\SecurityManager;
use Core\front\FrontCoreLoader;

$security = new SecurityManager(Request::capture(), 'front');
$noindex = $security->noindexFrontMiddleware();

return [
    [
        'method' => 'GET',
        'path' => '/',
        'handler' => static function (Request $request): \Core\http\Response {
            $frontContext = (new FrontCoreLoader())->boot();
            return View::make('home', [
                'request' => $request,
                'frontContext' => $frontContext,
            ], 'front');
        },
        'middleware' => [$noindex],
    ],
];
