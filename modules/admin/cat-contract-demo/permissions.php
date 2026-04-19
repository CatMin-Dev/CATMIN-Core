<?php

declare(strict_types=1);

return [
    'example.read' => [
        'name' => function_exists('__')
            ? __('module.cat-contract-demo.permissions.example.read.name')
            : 'Read contract demo module',
        'description' => function_exists('__')
            ? __('module.cat-contract-demo.permissions.example.read.description')
            : 'Allows reading contract demo pages and records.',
        'group' => 'example',
    ],
    'example.write' => [
        'name' => function_exists('__')
            ? __('module.cat-contract-demo.permissions.example.write.name')
            : 'Edit contract demo module',
        'description' => function_exists('__')
            ? __('module.cat-contract-demo.permissions.example.write.description')
            : 'Allows creating and updating contract demo data.',
        'group' => 'example',
    ],
    'example.delete' => [
        'name' => function_exists('__')
            ? __('module.cat-contract-demo.permissions.example.delete.name')
            : 'Delete in contract demo module',
        'description' => function_exists('__')
            ? __('module.cat-contract-demo.permissions.example.delete.description')
            : 'Allows deleting contract demo records.',
        'group' => 'example',
    ],
    'example.settings' => [
        'name' => function_exists('__')
            ? __('module.cat-contract-demo.permissions.example.settings.name')
            : 'Manage contract demo settings',
        'description' => function_exists('__')
            ? __('module.cat-contract-demo.permissions.example.settings.description')
            : 'Allows changing contract demo configuration.',
        'group' => 'example',
    ],
    'example.tools' => [
        'name' => function_exists('__')
            ? __('module.cat-contract-demo.permissions.example.tools.name')
            : 'Use contract demo tools',
        'description' => function_exists('__')
            ? __('module.cat-contract-demo.permissions.example.tools.description')
            : 'Allows executing contract demo maintenance tools.',
        'group' => 'example',
    ],
];
