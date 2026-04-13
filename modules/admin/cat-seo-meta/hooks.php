<?php

declare(strict_types=1);

require_once __DIR__ . '/services/SeoIntegrationService.php';

use Modules\CatSeoMeta\services\SeoIntegrationService;

$integration = new SeoIntegrationService();

if (function_exists('catmin_hook_register')) {
    catmin_hook_register($integration->panelHookName(), static function (mixed $panels, array $context) use ($integration): array {
        $panels = is_array($panels) ? $panels : [];
        $moduleSlug = strtolower(trim((string) ($context['module_slug'] ?? '')));
        if (!in_array($moduleSlug, $integration->targetConsumerModules(), true)) {
            return $panels;
        }

        $panels[] = $integration->buildPanelDescriptor();
        return $panels;
    });
}

return [
    'module' => 'cat-seo-meta',
    'hooks' => [
        $integration->panelHookName(),
    ],
];
