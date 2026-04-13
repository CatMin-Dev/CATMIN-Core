<?php

declare(strict_types=1);

use Modules\CatAuthors\services\AuthorIntegrationService;

require_once __DIR__ . '/services/AuthorIntegrationService.php';

catmin_hook_register('content.editor.panels', static function (array $panels, array $context): array {
    $service = new AuthorIntegrationService();
    return $service->appendPanel($panels, $context);
}, 90);
