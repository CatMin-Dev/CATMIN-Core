<?php

declare(strict_types=1);

return [
    'default' => 'sqlite',
    'connections' => [
        'sqlite' => [
            'driver' => 'sqlite',
            'database' => base_path('storage/database.sqlite'),
        ],
    ],
    'prefixes' => [
        'admin' => 'admin_',
        'core' => 'core_',
        'front' => 'front_',
        'module' => 'module_{slug}_',
    ],
];
