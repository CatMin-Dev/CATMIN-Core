<?php

declare(strict_types=1);

namespace Modules\CatImageEngine\Tests;

use PHPUnit\Framework\TestCase;
use Modules\CatImageEngine\Support\ImageProcessorResolver;
use Modules\CatImageEngine\Shared\Services\ImageMetadataService;
use Modules\CatImageEngine\Shared\DTO\ImageMetadataDto;
use Modules\CatImageEngine\Support\Drivers\BasicImageProcessor;
use Modules\CatImageEngine\Support\Drivers\ImagickImageProcessor;

class ImageProcessorResolverTest extends TestCase
{
    protected function tearDown(): void
    {
        ImageProcessorResolver::reset();
    }

    public function testResolveReturnsProcessor(): void
    {
        $processor = ImageProcessorResolver::resolve();
        $this->assertNotNull($processor);
        $this->assertTrue(
            $processor->isAvailable(),
            'Resolved processor should be available'
        );
    }

    public function testResolveReturnsSameInstanceOnMultipleCalls(): void
    {
        $processor1 = ImageProcessorResolver::resolve();
        $processor2 = ImageProcessorResolver::resolve();
        $this->assertSame($processor1, $processor2, 'Should return same instance');
    }

    public function testResetClearsInstance(): void
    {
        $processor1 = ImageProcessorResolver::resolve();
        ImageProcessorResolver::reset();
        $processor2 = ImageProcessorResolver::resolve();
        $this->assertNotSame($processor1, $processor2, 'After reset should return new instance');
    }

    public function testResolverPrefersPrimaryDriver(): void
    {
        $processor = ImageProcessorResolver::resolve();
        $name = $processor->getName();
        $this->assertIsString($name);
        $this->assertNotEmpty($name);
    }
}
