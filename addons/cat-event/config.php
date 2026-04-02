<?php

return [
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
