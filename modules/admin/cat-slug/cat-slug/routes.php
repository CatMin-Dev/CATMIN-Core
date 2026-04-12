<?php

declare(strict_types=1);

use Core\http\Request;
use Modules\CatSlug\controllers\SlugAdminController;

require_once __DIR__ . '/controllers/SlugAdminController.php';
require_once __DIR__ . '/repositories/SlugRegistryRepository.php';
require_once __DIR__ . '/services/SlugNormalizerService.php';
require_once __DIR__ . '/services/SlugGeneratorService.php';
require_once __DIR__ . '/services/SlugCollisionService.php';
require_once __DIR__ . '/services/SlugRegistryService.php';
require_once __DIR__ . '/services/SlugValidationService.php';

$controller = new SlugAdminController();

return [
    [
        'method' => 'GET',
        'path' => '/slug-bridge',
        'handler' => static fn (Request $request) => $controller->index($request),
        'middleware' => ['auth.admin'],
    ],
    [
        'method' => 'POST',
        'path' => '/slug-bridge/generate',
        'handler' => static fn (Request $request) => $controller->generate($request),
        'middleware' => ['auth.admin', 'csrf.verify'],
    ],
    [
        'method' => 'POST',
        'path' => '/slug-bridge/validate',
        'handler' => static fn (Request $request) => $controller->validate($request),
        'middleware' => ['auth.admin', 'csrf.verify'],
    ],
];
