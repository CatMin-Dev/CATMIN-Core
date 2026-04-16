<?php

declare(strict_types=1);

/**
 * CAT-IMAGE-ENGINE Bootstrap
 */

use Modules\CatImageEngine\Shared\Services\ImageMetadataService;
use Modules\CatImageEngine\Shared\Services\ImageTransformService;
use Modules\CatImageEngine\Shared\Services\ImageVariantService;

return [
    'name' => 'cat-image-engine',
    'version' => '0.1.0-dev',
    'enabled' => true,

    // Services
    'services' => [
        ImageMetadataService::class,
        ImageTransformService::class,
        ImageVariantService::class,
    ],

    // Config files
    'config' => [
        'image_engine' => base_path('modules/addons/cat-image-engine/config/image_engine.php'),
    ],

    // Permissions
    'permissions' => [
        'image_engine.process' => 'Process images',
        'image_engine.configure' => 'Configure image engine',
    ],
];
