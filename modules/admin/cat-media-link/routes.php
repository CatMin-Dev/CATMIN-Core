<?php

declare(strict_types=1);

use Core\http\Request;
use Modules\CatMediaLink\controllers\MediaLinkAdminController;

require_once __DIR__ . '/controllers/MediaLinkAdminController.php';
require_once __DIR__ . '/repositories/MediaLinkRepository.php';
require_once __DIR__ . '/services/MediaLinkValidationService.php';
require_once __DIR__ . '/services/FeaturedMediaService.php';
require_once __DIR__ . '/services/MediaGalleryService.php';
require_once __DIR__ . '/services/MediaUsageService.php';
require_once __DIR__ . '/services/MediaLinkService.php';
require_once __DIR__ . '/services/MediaLinkIntegrationService.php';

$controller = new MediaLinkAdminController();

return [
    [
        'method' => 'GET',
        'path' => '/modules/media-link',
        'handler' => static fn (Request $request) => $controller->index($request),
        'middleware' => ['auth.admin'],
    ],
    [
        'method' => 'POST',
        'path' => '/modules/media-link/upload',
        'handler' => static fn (Request $request) => $controller->upload($request),
        'middleware' => ['auth.admin', 'csrf.verify'],
    ],
    [
        'method' => 'POST',
        'path' => '/modules/media-link/add-url',
        'handler' => static fn (Request $request) => $controller->addUrl($request),
        'middleware' => ['auth.admin', 'csrf.verify'],
    ],
    [
        'method' => 'POST',
        'path' => '/modules/media-link/sync',
        'handler' => static fn (Request $request) => $controller->sync($request),
        'middleware' => ['auth.admin', 'csrf.verify'],
    ],
    [
        'method' => 'GET',
        'path' => '/modules/media-link/panel',
        'handler' => static fn (Request $request) => $controller->panel($request),
        'middleware' => ['auth.admin'],
    ],
    [
        'method' => 'GET',
        'path' => '/modules/media-link/assets/admin.js',
        'handler' => static fn (Request $request) => $controller->adminScript($request),
        'middleware' => ['auth.admin'],
    ],
];
