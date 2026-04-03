<?php

return [
    'navigation_items' => [
        [
            'label' => 'Carte',
            'icon' => 'bi bi-map',
            'route' => 'map.index',
            'active_when' => ['map.*'],
            'permission' => 'module.map.menu',
        ],
    ],

    'slug' => 'catmin-map',
    'category' => 'feature',
];