<?php

declare(strict_types=1);

namespace Modules\CatImageEngine\Support\Drivers;

use Modules\CatImageEngine\Shared\Contracts\ImageProcessorInterface;
use Modules\CatImageEngine\Shared\DTO\ImageMetadataDto;
use Modules\CatImageEngine\Shared\DTO\CropInstructionDto;
use Modules\CatImageEngine\Shared\DTO\ResizeInstructionDto;
use Modules\CatImageEngine\Shared\DTO\VariantPresetDto;
use Modules\CatImageEngine\Shared\DTO\TransformResultDto;

/**
 * Basic/GD fallback image processor
 */
final class BasicImageProcessor implements ImageProcessorInterface
{
    private bool $available = false;

    public function __construct()
    {
        $this->available = extension_loaded('gd');
    }

    public function isAvailable(): bool
    {
        return $this->available;
    }

    public function getName(): string
    {
        return 'BasicGD';
    }

    public function readMetadata(string $path): ImageMetadataDto
    {
        if (!is_file($path)) {
            throw new \RuntimeException("File not found: $path");
        }

        try {
            $info = @getimagesize($path);
            if ($info === false) {
                throw new \RuntimeException('Cannot read image size');
            }

            $mimeType = $info['mime'] ?? 'application/octet-stream';
            $format = strtolower(str_replace('image/', '', $mimeType));

            return new ImageMetadataDto(
                path: $path,
                width: (int) $info[0],
                height: (int) $info[1],
                format: $format,
                mimeType: $mimeType,
                filesize: filesize($path),
            );
        } catch (\Throwable $e) {
            throw new \RuntimeException('Failed to read image metadata: ' . $e->getMessage());
        }
    }

    public function crop(string $sourcePath, CropInstructionDto $instruction): TransformResultDto
    {
        if (!$this->available) {
            return TransformResultDto::failure($sourcePath, 'GD not available');
        }

        return TransformResultDto::failure($sourcePath, 'Crop not supported by BasicGD processor');
    }

    public function resize(string $sourcePath, ResizeInstructionDto $instruction): TransformResultDto
    {
        if (!$this->available) {
            return TransformResultDto::failure($sourcePath, 'GD not available');
        }

        try {
            $startTime = microtime(true);

            $source = $this->loadImage($sourcePath);
            if (!$source) {
                return TransformResultDto::failure($sourcePath, 'Cannot load image');
            }

            $srcWidth = imagesx($source);
            $srcHeight = imagesy($source);

            // Simple resize (no sophisticated mode handling)
            $dest = imagecreatetruecolor($instruction->width, $instruction->height);
            if (!$dest) {
                imagedestroy($source);
                return TransformResultDto::failure($sourcePath, 'Cannot create destination image');
            }

            if (!imagecopyresampled($dest, $source, 0, 0, 0, 0, $instruction->width, $instruction->height, $srcWidth, $srcHeight)) {
                imagedestroy($source);
                imagedestroy($dest);
                return TransformResultDto::failure($sourcePath, 'Resize operation failed');
            }

            $targetPath = $this->getOutputPath($sourcePath, $instruction->format);
            $this->saveImage($dest, $targetPath, $instruction->format, $instruction->quality);

            imagedestroy($source);
            imagedestroy($dest);

            $duration = (int) ((microtime(true) - $startTime) * 1000);
            return TransformResultDto::success($sourcePath, $targetPath, $duration);
        } catch (\Throwable $e) {
            return TransformResultDto::failure($sourcePath, 'Resize failed: ' . $e->getMessage());
        }
    }

    public function rotate(string $sourcePath, int $angle): TransformResultDto
    {
        return TransformResultDto::failure($sourcePath, 'Rotation not supported by BasicGD processor');
    }

    public function convertFormat(string $sourcePath, string $format, array $options = []): TransformResultDto
    {
        if (!$this->available) {
            return TransformResultDto::failure($sourcePath, 'GD not available');
        }

        try {
            $startTime = microtime(true);

            $source = $this->loadImage($sourcePath);
            if (!$source) {
                return TransformResultDto::failure($sourcePath, 'Cannot load image');
            }

            $quality = (int) ($options['quality'] ?? 85);
            $targetPath = $this->getOutputPath($sourcePath, $format);
            $this->saveImage($source, $targetPath, $format, $quality);
            imagedestroy($source);

            $duration = (int) ((microtime(true) - $startTime) * 1000);
            return TransformResultDto::success($sourcePath, $targetPath, $duration);
        } catch (\Throwable $e) {
            return TransformResultDto::failure($sourcePath, 'Format conversion failed: ' . $e->getMessage());
        }
    }

    public function generateVariant(string $sourcePath, VariantPresetDto $preset): TransformResultDto
    {
        $resizeInstruction = new ResizeInstructionDto(
            width: $preset->width,
            height: $preset->height,
            mode: $preset->mode,
            quality: $preset->quality,
            format: $preset->format,
        );
        return $this->resize($sourcePath, $resizeInstruction);
    }

    private function loadImage(string $path): ?\GdImage
    {
        $info = @getimagesize($path);
        if ($info === false) {
            return null;
        }

        $mimeType = $info['mime'] ?? '';

        return match ($mimeType) {
            'image/jpeg' => @imagecreatefromjpeg($path),
            'image/png' => @imagecreatefrompng($path),
            'image/gif' => @imagecreatefromgif($path),
            'image/webp' => @imagecreatefromwebp($path),
            default => null,
        };
    }

    private function saveImage(\GdImage $image, string $path, string $format, int $quality): void
    {
        match (strtolower($format)) {
            'jpeg', 'jpg' => imagejpeg($image, $path, $quality),
            'png' => imagepng($image, $path),
            'gif' => imagegif($image, $path),
            'webp' => imagewebp($image, $path, $quality),
            default => imagejpeg($image, $path, $quality),
        };
    }

    private function getOutputPath(string $sourcePath, string $format): string
    {
        $pathInfo = pathinfo($sourcePath);
        $dir = $pathInfo['dirname'];
        $filename = $pathInfo['filename'];
        return "$dir/{$filename}_transformed.$format";
    }
}
