<?php

declare(strict_types=1);

return [
    'example_module.enabled' => [
        'group' => 'example_module',
        'type' => 'bool',
        'default' => true,
        'autoload' => false,
        'protected' => false,
        'system' => false,
    ],
    'example_module.title' => [
        'group' => 'example_module',
        'type' => 'string',
        'default' => 'Contract Demo',
        'autoload' => false,
        'protected' => false,
        'system' => false,
    ],
];
