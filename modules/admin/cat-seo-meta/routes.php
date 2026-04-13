<?php

declare(strict_types=1);

use Core\http\Request;
use Modules\CatSeoMeta\controllers\SeoMetaAdminController;

require_once __DIR__ . '/controllers/SeoMetaAdminController.php';
require_once __DIR__ . '/repositories/SeoMetaRepository.php';
require_once __DIR__ . '/services/SeoScoreService.php';
require_once __DIR__ . '/services/SeoAuditService.php';
require_once __DIR__ . '/services/SeoPreviewService.php';
require_once __DIR__ . '/services/SeoMetaService.php';
require_once __DIR__ . '/services/SeoIntegrationService.php';

$controller = new SeoMetaAdminController();

return [
    [
        'method' => 'GET',
        'path' => '/modules/seo-meta',
        'handler' => static fn (Request $request) => $controller->index($request),
        'middleware' => ['auth.admin'],
    ],
    [
        'method' => 'POST',
        'path' => '/modules/seo-meta/save',
        'handler' => static fn (Request $request) => $controller->save($request),
        'middleware' => ['auth.admin', 'csrf.verify'],
    ],
    [
        'method' => 'POST',
        'path' => '/modules/seo-meta/audit',
        'handler' => static fn (Request $request) => $controller->audit($request),
        'middleware' => ['auth.admin', 'csrf.verify'],
    ],
];
