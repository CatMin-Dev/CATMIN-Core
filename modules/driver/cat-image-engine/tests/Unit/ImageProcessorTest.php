<?php

declare(strict_types=1);

namespace Modules\CatImageEngine\Tests;

use PHPUnit\Framework\TestCase;
use Modules\CatImageEngine\Support\Drivers\BasicImageProcessor;
use Modules\CatImageEngine\Support\Drivers\ImagickImageProcessor;

class ImageProcessorTest extends TestCase
{
    public function testBasicImageProcessorAvailability(): void
    {
        $processor = new BasicImageProcessor();
        $isAvailable = $processor->isAvailable();
        
        // Should be true or false, not null
        $this->assertIsBool($isAvailable);
        $this->assertNotNull($isAvailable);
    }

    public function testBasicImageProcessorName(): void
    {
        $processor = new BasicImageProcessor();
        $this->assertEquals('BasicGD', $processor->getName());
    }

    public function testImagickImageProcessorName(): void
    {
        $processor = new ImagickImageProcessor();
        $this->assertEquals('Imagick', $processor->getName());
    }

    public function testImagickImageProcessorAvailability(): void
    {
        $processor = new ImagickImageProcessor();
        $isAvailable = $processor->isAvailable();
        
        // Should always return a boolean
        $this->assertIsBool($isAvailable);
        $this->assertNotNull($isAvailable);
    }

    public function testProcessorInterfaceImplementation(): void
    {
        $basic = new BasicImageProcessor();
        $this->assertInstanceOf(
            \Modules\CatImageEngine\Shared\Contracts\ImageProcessorInterface::class,
            $basic
        );

        $imagick = new ImagickImageProcessor();
        $this->assertInstanceOf(
            \Modules\CatImageEngine\Shared\Contracts\ImageProcessorInterface::class,
            $imagick
        );
    }
}
