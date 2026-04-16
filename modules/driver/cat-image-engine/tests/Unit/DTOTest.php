<?php

declare(strict_types=1);

namespace Modules\CatImageEngine\Tests;

use PHPUnit\Framework\TestCase;
use Modules\CatImageEngine\Shared\DTO\ImageMetadataDto;
use Modules\CatImageEngine\Shared\DTO\CropInstructionDto;
use Modules\CatImageEngine\Shared\DTO\ResizeInstructionDto;
use Modules\CatImageEngine\Shared\DTO\VariantPresetDto;
use Modules\CatImageEngine\Shared\DTO\TransformResultDto;

class DTOTest extends TestCase
{
    public function testImageMetadataDtoConstruction(): void
    {
        $dto = new ImageMetadataDto(
            path: '/tmp/test.jpg',
            width: 100,
            height: 100,
            format: 'jpeg',
            mimeType: 'image/jpeg',
            filesize: 5000,
        );

        $this->assertEquals('/tmp/test.jpg', $dto->path);
        $this->assertEquals(100, $dto->width);
        $this->assertEquals(100, $dto->height);
    }

    public function testCropInstructionDtoFromArray(): void
    {
        $data = [
            'x' => 10,
            'y' => 20,
            'width' => 200,
            'height' => 200,
            'format' => 'png',
            'quality' => 90,
        ];

        $dto = CropInstructionDto::fromArray($data);
        $this->assertEquals(10, $dto->x);
        $this->assertEquals(20, $dto->y);
        $this->assertEquals(200, $dto->width);
        $this->assertEquals(200, $dto->height);
        $this->assertEquals('png', $dto->format);
        $this->assertEquals(90, $dto->quality);
    }

    public function testResizeInstructionDtoToArray(): void
    {
        $dto = new ResizeInstructionDto(
            width: 800,
            height: 600,
            mode: 'contain',
            quality: 85,
            format: 'webp',
        );

        $array = $dto->toArray();
        $this->assertEquals(800, $array['width']);
        $this->assertEquals(600, $array['height']);
        $this->assertEquals('contain', $array['mode']);
    }

    public function testVariantPresetDtoDefaults(): void
    {
        $dto = new VariantPresetDto(
            name: 'thumbnail',
            width: 150,
            height: 150,
        );

        $this->assertEquals('thumbnail', $dto->name);
        $this->assertEquals('cover', $dto->mode);
        $this->assertEquals('jpeg', $dto->format);
        $this->assertEquals(85, $dto->quality);
    }

    public function testTransformResultDtoSuccess(): void
    {
        $result = TransformResultDto::success('/src.jpg', '/dest.jpg', 150);
        $this->assertTrue($result->success);
        $this->assertEquals('/src.jpg', $result->originalPath);
        $this->assertEquals('/dest.jpg', $result->transformPath);
        $this->assertNull($result->errorMessage);
        $this->assertEquals(150, $result->durationMs);
    }

    public function testTransformResultDtoFailure(): void
    {
        $result = TransformResultDto::failure('/src.jpg', 'Test error');
        $this->assertFalse($result->success);
        $this->assertNull($result->transformPath);
        $this->assertEquals('Test error', $result->errorMessage);
    }
}
