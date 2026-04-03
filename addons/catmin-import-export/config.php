<?php

return [
    'navigation_items' => [
        [
            'label' => 'Import / Export',
            'icon' => 'bi bi-arrow-left-right',
            'route' => 'import_export.index',
            'active_when' => ['import_export.*'],
            'permission' => 'module.import_export.menu',
        ],
    ],

    'slug' => 'catmin-import-export',
    'category' => 'feature',
];