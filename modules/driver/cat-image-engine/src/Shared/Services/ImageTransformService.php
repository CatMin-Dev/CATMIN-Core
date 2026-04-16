<?php

declare(strict_types=1);

namespace Modules\CatImageEngine\Shared\Services;

use Modules\CatImageEngine\Support\ImageProcessorResolver;
use Modules\CatImageEngine\Shared\DTO\CropInstructionDto;
use Modules\CatImageEngine\Shared\DTO\ResizeInstructionDto;
use Modules\CatImageEngine\Shared\DTO\TransformResultDto;

/**
 * Image transform service
 */
final class ImageTransformService
{
    /**
     * Crop image
     */
    public function crop(string $sourcePath, CropInstructionDto $instruction): TransformResultDto
    {
        $processor = ImageProcessorResolver::resolve();
        return $processor->crop($sourcePath, $instruction);
    }

    /**
     * Resize image
     */
    public function resize(string $sourcePath, ResizeInstructionDto $instruction): TransformResultDto
    {
        $processor = ImageProcessorResolver::resolve();
        return $processor->resize($sourcePath, $instruction);
    }

    /**
     * Rotate image
     */
    public function rotate(string $sourcePath, int $angle): TransformResultDto
    {
        $processor = ImageProcessorResolver::resolve();
        return $processor->rotate($sourcePath, $angle);
    }

    /**
     * Convert image format
     */
    public function convertFormat(string $sourcePath, string $format, array $options = []): TransformResultDto
    {
        $processor = ImageProcessorResolver::resolve();
        return $processor->convertFormat($sourcePath, $format, $options);
    }
}
