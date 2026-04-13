<?php

declare(strict_types=1);

namespace Modules\CatMenuLink\services;

final class MenuLinkIntegrationService
{
    public function targetConsumerModules(): array
    {
        return ['cat-page', 'cat-blog', 'cat-directory'];
    }

    public function mandatoryDependenciesForConsumer(): array
    {
        return ['cat-menu-link'];
    }

    public function panelHookName(): string
    {
        return 'content.editor.panels';
    }

    public function buildPanelDescriptor(): array
    {
        return [
            'key' => 'menu',
            'label' => 'Menu',
            'view' => CATMIN_MODULES . '/admin/cat-menu-link/views/embedded_panel.php',
            'order' => 87,
            'fields' => ['menu_key', 'menu_label_override', 'menu_parent_item_id', 'menu_sort_order', 'menu_visible'],
            'required_modules' => $this->mandatoryDependenciesForConsumer(),
        ];
    }
}
