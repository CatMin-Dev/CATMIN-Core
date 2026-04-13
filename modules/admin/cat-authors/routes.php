<?php

declare(strict_types=1);

use Core\http\Request;
use Modules\CatAuthors\controllers\AuthorAdminController;

require_once __DIR__ . '/controllers/AuthorAdminController.php';
require_once __DIR__ . '/repositories/AuthorRepository.php';
require_once __DIR__ . '/services/AuthorValidationService.php';
require_once __DIR__ . '/services/AuthorProfileService.php';
require_once __DIR__ . '/services/AuthorLinkService.php';
require_once __DIR__ . '/services/AuthorSelectorService.php';
require_once __DIR__ . '/services/AuthorDisplayService.php';
require_once __DIR__ . '/services/AuthorRoleRegistryService.php';
require_once __DIR__ . '/services/AuthorIntegrationService.php';

$controller = new AuthorAdminController();

return [
    // Main page (profiles + roles tabs)
    [
        'method'     => 'GET',
        'path'       => '/modules/author-bridge',
        'handler'    => static fn (Request $request) => $controller->index($request),
        'middleware' => ['auth.admin'],
    ],
    // Create profile
    [
        'method'     => 'POST',
        'path'       => '/modules/author-bridge/profile/create',
        'handler'    => static fn (Request $request) => $controller->createProfile($request),
        'middleware' => ['auth.admin', 'csrf.verify'],
    ],
    // Update profile
    [
        'method'     => 'POST',
        'path'       => '/modules/author-bridge/profile/update',
        'handler'    => static fn (Request $request) => $controller->updateProfile($request),
        'middleware' => ['auth.admin', 'csrf.verify'],
    ],
    // Delete profile
    [
        'method'     => 'POST',
        'path'       => '/modules/author-bridge/profile/delete',
        'handler'    => static fn (Request $request) => $controller->deleteProfile($request),
        'middleware' => ['auth.admin', 'csrf.verify'],
    ],
    // Sync entity → author (AJAX POST)
    [
        'method'     => 'POST',
        'path'       => '/modules/author-bridge/sync',
        'handler'    => static fn (Request $request) => $controller->syncEntity($request),
        'middleware' => ['auth.admin', 'csrf.verify'],
    ],
    // Save role registry
    [
        'method'     => 'POST',
        'path'       => '/modules/author-bridge/roles/save',
        'handler'    => static fn (Request $request) => $controller->saveRoleRegistry($request),
        'middleware' => ['auth.admin', 'csrf.verify'],
    ],
    // Embedded panel (GET — HTML fragment for module editors)
    [
        'method'     => 'GET',
        'path'       => '/modules/author-bridge/panel',
        'handler'    => static fn (Request $request) => $controller->panel($request),
        'middleware' => ['auth.admin'],
    ],
];
