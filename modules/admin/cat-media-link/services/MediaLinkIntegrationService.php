<?php

declare(strict_types=1);

namespace Modules\CatMediaLink\services;

final class MediaLinkIntegrationService
{
    public function targetConsumerModules(): array
    {
        return ['cat-page', 'cat-blog', 'cat-directory'];
    }

    public function mandatoryDependenciesForConsumer(): array
    {
        return ['cat-media-link'];
    }

    public function panelHookName(): string
    {
        return 'content.editor.panels';
    }

    public function buildPanelDescriptor(): array
    {
        return [
            'key' => 'media',
            'label' => 'Media',
            'view' => CATMIN_MODULES . '/admin/cat-media-link/views/embedded_panel.php',
            'order' => 88,
            'fields' => ['featured_media_id', 'gallery_media_ids', 'social_media_id'],
            'required_modules' => $this->mandatoryDependenciesForConsumer(),
        ];
    }
}
