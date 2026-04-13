<?php

declare(strict_types=1);

use Core\http\Request;
use Modules\CatTags\controllers\TagsAdminController;

require_once __DIR__ . '/controllers/TagsAdminController.php';
require_once __DIR__ . '/repositories/TagsRepository.php';
require_once __DIR__ . '/services/TagUsageService.php';
require_once __DIR__ . '/services/TagLinkService.php';
require_once __DIR__ . '/services/TagAutocompleteService.php';
require_once __DIR__ . '/services/TagDisplayService.php';
require_once __DIR__ . '/services/TagsService.php';
require_once __DIR__ . '/services/TagsIntegrationService.php';

$controller = new TagsAdminController();

return [
    [
        'method' => 'GET',
        'path' => '/modules/tags-bridge',
        'handler' => static fn (Request $request) => $controller->index($request),
        'middleware' => ['auth.admin'],
    ],
    [
        'method' => 'POST',
        'path' => '/modules/tags-bridge/sync',
        'handler' => static fn (Request $request) => $controller->sync($request),
        'middleware' => ['auth.admin', 'csrf.verify'],
    ],
    [
        'method' => 'GET',
        'path' => '/modules/tags-bridge/suggest',
        'handler' => static fn (Request $request) => $controller->suggest($request),
        'middleware' => ['auth.admin'],
    ],
];
