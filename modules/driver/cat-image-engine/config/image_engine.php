<?php

declare(strict_types=1);

return [
    'driver' => env('IMAGE_ENGINE_DRIVER', 'imagick'),
    'fallback_allowed' => env('IMAGE_ENGINE_FALLBACK', true),
    'quality' => env('IMAGE_ENGINE_QUALITY', 85),
    'output_format' => env('IMAGE_ENGINE_FORMAT', 'jpeg'),
    'max_dimension' => env('IMAGE_ENGINE_MAX_DIMENSION', 4000),
    'variants' => [
        'thumbnail' => [
            'name' => 'thumbnail',
            'width' => 150,
            'height' => 150,
            'mode' => 'cover',
            'quality' => 80,
            'format' => 'jpeg',
        ],
        'medium' => [
            'name' => 'medium',
            'width' => 600,
            'height' => 600,
            'mode' => 'cover',
            'quality' => 85,
            'format' => 'jpeg',
        ],
        'large' => [
            'name' => 'large',
            'width' => 1200,
            'height' => 1200,
            'mode' => 'cover',
            'quality' => 90,
            'format' => 'jpeg',
        ],
    ],
];
