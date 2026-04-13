<?php

declare(strict_types=1);

use Core\http\Request;
use Modules\CatCategories\controllers\CategoriesAdminController;

require_once __DIR__ . '/controllers/CategoriesAdminController.php';
require_once __DIR__ . '/repositories/CategoriesRepository.php';
require_once __DIR__ . '/services/CategoryTreeService.php';
require_once __DIR__ . '/services/CategoryLinkService.php';
require_once __DIR__ . '/services/CategoryUsageService.php';
require_once __DIR__ . '/services/CategorySelectorService.php';
require_once __DIR__ . '/services/CategoriesService.php';
require_once __DIR__ . '/services/CategoriesIntegrationService.php';

$controller = new CategoriesAdminController();

return [
    [
        'method' => 'GET',
        'path' => '/modules/categories-bridge',
        'handler' => static fn (Request $request) => $controller->index($request),
        'middleware' => ['auth.admin'],
    ],
    [
        'method' => 'POST',
        'path' => '/modules/categories-bridge/create',
        'handler' => static fn (Request $request) => $controller->create($request),
        'middleware' => ['auth.admin', 'csrf.verify'],
    ],
    [
        'method' => 'POST',
        'path' => '/modules/categories-bridge/sync',
        'handler' => static fn (Request $request) => $controller->sync($request),
        'middleware' => ['auth.admin', 'csrf.verify'],
    ],
    [
        'method' => 'GET',
        'path' => '/modules/categories-bridge/tree',
        'handler' => static fn (Request $request) => $controller->tree($request),
        'middleware' => ['auth.admin'],
    ],
];
