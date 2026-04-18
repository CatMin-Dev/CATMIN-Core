<?php

declare(strict_types=1);

final class CoreModuleUiAnchorRegistry
{
    /** @return array<int, string> */
    public function all(): array
    {
        return [
            'sidebar.main',
            'sidebar.settings',
            'topbar.actions',
            'topbar.indicators',
            'dashboard.widgets',
            'dashboard.cards',
            'dashboard.activity',
            'dashboard.monitoring',
            'page.header.actions',
            'page.footer.actions',
            'snippets.registry',
            'notifications.feed',
            'settings.sections',
            'admin.tools',
            'front.widgets',
            'front.blocks',
        ];
    }

    public function isAllowed(string $target): bool
    {
        $target = strtolower(trim($target));
        if ($target === '') {
            return false;
        }

        return in_array($target, $this->all(), true);
    }
}
