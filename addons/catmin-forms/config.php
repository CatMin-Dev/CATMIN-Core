<?php

return [
    'navigation_items' => [
        [
            'label' => 'Forms',
            'icon' => 'bi bi-ui-checks-grid',
            'route' => 'forms.index',
            'active_when' => ['forms.*'],
            'permission' => 'module.forms.menu',
        ],
    ],
    'honeypot_field' => 'website_url',
];
