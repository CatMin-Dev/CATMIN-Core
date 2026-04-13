<?php

declare(strict_types=1);

namespace Modules\CatCategories\services;

final class CategoriesIntegrationService
{
    public function targetConsumerModules(): array
    {
        return ['cat-page', 'cat-blog', 'cat-directory', 'cat-products'];
    }

    public function mandatoryDependenciesForConsumer(): array
    {
        return ['cat-seo-meta', 'cat-tags', 'cat-categories'];
    }

    public function panelHookName(): string
    {
        return 'content.editor.panels';
    }

    public function buildPanelDescriptor(): array
    {
        return [
            'key' => 'categories',
            'label' => 'Categories',
            'view' => CATMIN_MODULES . '/admin/cat-categories/views/embedded_panel.php',
            'order' => 86,
            'fields' => ['category_ids'],
            'required_modules' => $this->mandatoryDependenciesForConsumer(),
        ];
    }
}
