<?php

return [
    'navigation_items' => [
        [
            'label' => 'Booking',
            'icon' => 'bi bi-calendar-check',
            'route' => 'booking.services.index',
            'active_when' => ['booking.*'],
            'permission' => 'module.booking.menu',
        ],
    ],

    'slug' => 'catmin-booking',
    'category' => 'business',
];