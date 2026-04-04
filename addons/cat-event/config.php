<?php

return [
    'shop_redirect_pattern' => '/shop/products/{product_id}',

    'navigation_items' => [
        [
            'label'       => 'Événements',
            'icon'        => 'bi bi-calendar-event',
            'route'       => 'events.index',
            'active_when' => ['events.*'],
            'permission'  => 'module.events.menu',
        ],
    ],
];
