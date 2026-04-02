<?php

return [
    'navigation_items' => [
        [
            'label' => 'Shop',
            'icon' => 'bi bi-bag',
            'route' => 'shop.manage',
            'active_when' => ['shop.*'],
            'permission' => 'module.shop.menu',
        ],
        [
            'label' => 'Factures - Config',
            'icon' => 'bi bi-receipt',
            'route' => 'shop.invoices.settings',
            'active_when' => ['shop.invoices.settings*'],
            'permission' => 'module.shop.config',
        ],
    ],
];
