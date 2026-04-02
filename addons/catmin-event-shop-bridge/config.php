<?php

return [
    'navigation_items' => [
        [
            'label' => 'Event-Shop Bridge',
            'icon' => 'bi bi-diagram-3',
            'route' => 'event-shop-bridge.ticket-types.index',
            'active_when' => ['event-shop-bridge.*'],
            'permission' => 'module.event_shop_bridge.menu',
        ],
    ],
];