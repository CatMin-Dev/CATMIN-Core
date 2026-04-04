<?php

return [
    'navigation_items' => [
        [
            'label' => 'Backup distant',
            'icon' => 'bi bi-cloud-arrow-up',
            'route' => 'backup.remote.index',
            'active_when' => ['backup.remote.*'],
            'permission' => 'backup.remote.index',
        ],
    ],

    'slug' => 'catmin-backup-s3',
    'category' => 'ops',
];
