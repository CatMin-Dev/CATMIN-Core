<?php

declare(strict_types=1);

namespace Modules\CatImageEngine\Shared\Services;

use Modules\CatImageEngine\Support\ImageProcessorResolver;
use Modules\CatImageEngine\Shared\DTO\ImageMetadataDto;

/**
 * Image metadata service
 */
final class ImageMetadataService
{
    /**
     * Read image metadata from file path
     */
    public function readMetadata(string $path): ImageMetadataDto
    {
        $processor = ImageProcessorResolver::resolve();
        return $processor->readMetadata($path);
    }

    /**
     * Extract EXIF data if available
     */
    public function extractExif(string $path): array
    {
        try {
            $metadata = $this->readMetadata($path);
            return $metadata->exif ?? [];
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Get image dimensions
     */
    public function getDimensions(string $path): array
    {
        try {
            $metadata = $this->readMetadata($path);
            return [
                'width' => $metadata->width,
                'height' => $metadata->height,
            ];
        } catch (\Throwable) {
            return ['width' => 0, 'height' => 0];
        }
    }
}
