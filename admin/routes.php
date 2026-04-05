<?php

declare(strict_types=1);

use Core\http\Request;
use Core\http\View;

return [
    [
        'method' => 'GET',
        'path' => '/',
        'handler' => static function (Request $request): \Core\http\Response {
            return View::make('dashboard', ['request' => $request], 'admin');
        },
        'middleware' => [],
    ],
];
