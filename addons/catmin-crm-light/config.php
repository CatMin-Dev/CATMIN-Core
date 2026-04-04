<?php

return [
    'navigation_items' => [
        [
            'label' => 'CRM',
            'icon' => 'bi bi-people',
            'route' => 'crm.contacts.index',
            'active_when' => ['crm.*'],
            'permission' => 'module.crm.menu',
        ],
    ],

    'slug' => 'catmin-crm-light',
    'category' => 'business',
    'pipeline_stages' => ['new', 'contacted', 'qualified', 'won', 'lost', 'archived'],
];