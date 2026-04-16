<?php

declare(strict_types=1);

namespace Modules\CatImageEngine\Shared\Contracts;

use Modules\CatImageEngine\Shared\DTO\ImageMetadataDto;
use Modules\CatImageEngine\Shared\DTO\CropInstructionDto;
use Modules\CatImageEngine\Shared\DTO\ResizeInstructionDto;
use Modules\CatImageEngine\Shared\DTO\VariantPresetDto;
use Modules\CatImageEngine\Shared\DTO\TransformResultDto;

/**
 * Interface contract for image processors
 */
interface ImageProcessorInterface
{
    /**
     * Read image metadata
     */
    public function readMetadata(string $path): ImageMetadataDto;

    /**
     * Crop image
     */
    public function crop(string $sourcePath, CropInstructionDto $instruction): TransformResultDto;

    /**
     * Resize image
     */
    public function resize(string $sourcePath, ResizeInstructionDto $instruction): TransformResultDto;

    /**
     * Rotate image
     */
    public function rotate(string $sourcePath, int $angle): TransformResultDto;

    /**
     * Convert format
     */
    public function convertFormat(string $sourcePath, string $format, array $options = []): TransformResultDto;

    /**
     * Generate variant from preset
     */
    public function generateVariant(string $sourcePath, VariantPresetDto $preset): TransformResultDto;

    /**
     * Check if processor is available
     */
    public function isAvailable(): bool;

    /**
     * Get processor name
     */
    public function getName(): string;
}
