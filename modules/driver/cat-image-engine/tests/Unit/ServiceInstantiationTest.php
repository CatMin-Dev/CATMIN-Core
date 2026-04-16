<?php

declare(strict_types=1);

namespace Modules\CatImageEngine\Tests;

use PHPUnit\Framework\TestCase;
use Modules\CatImageEngine\Shared\Services\ImageMetadataService;
use Modules\CatImageEngine\Shared\Services\ImageTransformService;
use Modules\CatImageEngine\Shared\Services\ImageVariantService;

class ServiceInstantiationTest extends TestCase
{
    public function testImageMetadataServiceCanBeInstantiated(): void
    {
        $service = new ImageMetadataService();
        $this->assertInstanceOf(ImageMetadataService::class, $service);
    }

    public function testImageTransformServiceCanBeInstantiated(): void
    {
        $service = new ImageTransformService();
        $this->assertInstanceOf(ImageTransformService::class, $service);
    }

    public function testImageVariantServiceCanBeInstantiated(): void
    {
        $service = new ImageVariantService();
        $this->assertInstanceOf(ImageVariantService::class, $service);
    }

    public function testServicesHaveRequiredMethods(): void
    {
        $metadataService = new ImageMetadataService();
        $this->assertTrue(method_exists($metadataService, 'readMetadata'));
        $this->assertTrue(method_exists($metadataService, 'extractExif'));
        $this->assertTrue(method_exists($metadataService, 'getDimensions'));

        $transformService = new ImageTransformService();
        $this->assertTrue(method_exists($transformService, 'crop'));
        $this->assertTrue(method_exists($transformService, 'resize'));
        $this->assertTrue(method_exists($transformService, 'rotate'));
        $this->assertTrue(method_exists($transformService, 'convertFormat'));

        $variantService = new ImageVariantService();
        $this->assertTrue(method_exists($variantService, 'generateVariant'));
        $this->assertTrue(method_exists($variantService, 'generateVariants'));
    }
}
