<?php

declare(strict_types=1);

use Core\http\Request;
use Modules\CatMenuLink\controllers\MenuLinkAdminController;

require_once __DIR__ . '/controllers/MenuLinkAdminController.php';
require_once __DIR__ . '/repositories/MenuLinkRepository.php';
require_once __DIR__ . '/services/MenuLinkValidationService.php';
require_once __DIR__ . '/services/MenuAttachmentService.php';
require_once __DIR__ . '/services/MenuTreeService.php';
require_once __DIR__ . '/services/BreadcrumbSeedService.php';
require_once __DIR__ . '/services/MenuLinkService.php';
require_once __DIR__ . '/services/MenuLinkIntegrationService.php';

$controller = new MenuLinkAdminController();

return [
    [
        'method' => 'GET',
        'path' => '/modules/menu-link',
        'handler' => static fn (Request $request) => $controller->index($request),
        'middleware' => ['auth.admin'],
    ],
    [
        'method' => 'POST',
        'path' => '/modules/menu-link/attach',
        'handler' => static fn (Request $request) => $controller->attach($request),
        'middleware' => ['auth.admin', 'csrf.verify'],
    ],
    [
        'method' => 'POST',
        'path' => '/modules/menu-link/reorder',
        'handler' => static fn (Request $request) => $controller->reorder($request),
        'middleware' => ['auth.admin', 'csrf.verify'],
    ],
    [
        'method' => 'POST',
        'path' => '/modules/menu-link/delete',
        'handler' => static fn (Request $request) => $controller->delete($request),
        'middleware' => ['auth.admin', 'csrf.verify'],
    ],
    [
        'method' => 'GET',
        'path' => '/modules/menu-link/panel',
        'handler' => static fn (Request $request) => $controller->panel($request),
        'middleware' => ['auth.admin'],
    ],
    [
        'method' => 'GET',
        'path' => '/modules/menu-link/assets/admin.js',
        'handler' => static fn (Request $request) => $controller->adminScript($request),
        'middleware' => ['auth.admin'],
    ],
];
