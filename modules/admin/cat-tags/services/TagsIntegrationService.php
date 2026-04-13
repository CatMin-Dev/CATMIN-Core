<?php

declare(strict_types=1);

namespace Modules\CatTags\services;

final class TagsIntegrationService
{
    public function targetConsumerModules(): array
    {
        return ['cat-page', 'cat-blog', 'cat-directory'];
    }

    public function mandatoryDependenciesForConsumer(): array
    {
        return ['cat-seo-meta', 'cat-tags'];
    }

    public function panelHookName(): string
    {
        return 'content.editor.panels';
    }

    public function buildPanelDescriptor(): array
    {
        return [
            'key' => 'tags',
            'label' => 'Tags',
            'view' => CATMIN_MODULES . '/admin/cat-tags/views/embedded_panel.php',
            'order' => 85,
            'fields' => ['tags_csv'],
            'required_modules' => $this->mandatoryDependenciesForConsumer(),
        ];
    }
}
