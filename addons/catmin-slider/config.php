<?php

return [
    'navigation_items' => [
        [
            'label' => 'Sliders',
            'icon' => 'bi bi-collection-play',
            'route' => 'slider.index',
            'active_when' => ['slider.*'],
            'permission' => 'slider.index',
        ],
    ],

    'slug' => 'catmin-slider',
    'category' => 'cms',
];
