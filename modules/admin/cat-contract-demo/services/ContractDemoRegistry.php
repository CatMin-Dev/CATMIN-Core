<?php

declare(strict_types=1);

final class ContractDemoRegistry
{
    /** @return array<int, string> */
    public function capabilities(): array
    {
        return [
            'routes.admin',
            'routes.front',
            'routes.api',
            'routes.ajax',
            'routes.settings',
            'navigation.sidebar',
            'ui.inject',
            'permissions',
            'settings',
            'notifications',
            'assets',
        ];
    }
}
