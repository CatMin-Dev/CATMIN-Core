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
 * Imagick-based image processor
 */
final class ImagickImageProcessor implements ImageProcessorInterface
{
    private bool $available = false;

    public function __construct()
    {
        $this->available = extension_loaded('imagick') && class_exists('Imagick');
    }

    public function isAvailable(): bool
    {
        return $this->available;
    }

    public function getName(): string
    {
        return 'Imagick';
    }

    public function readMetadata(string $path): ImageMetadataDto
    {
        if (!is_file($path)) {
            throw new \RuntimeException("File not found: $path");
        }

        if (!$this->available) {
            throw new \RuntimeException('Imagick not available');
        }

        try {
            $imagick = new \Imagick($path);
            $metadata = new ImageMetadataDto(
                path: $path,
                width: (int) $imagick->getImageWidth(),
                height: (int) $imagick->getImageHeight(),
                format: strtolower($imagick->getImageFormat()),
                mimeType: $imagick->getImageMimeType() ?: 'application/octet-stream',
                filesize: filesize($path),
                colorspace: (string) $imagick->getImageColorspace(),
                compression: (string) $imagick->getImageCompression(),
                exif: $this->extractExif($imagick),
            );
            $imagick->destroy();
            return $metadata;
        } catch (\Throwable $e) {
            throw new \RuntimeException('Failed to read image metadata: ' . $e->getMessage());
        }
    }

    public function crop(string $sourcePath, CropInstructionDto $instruction): TransformResultDto
    {
        if (!$this->available) {
            return TransformResultDto::failure($sourcePath, 'Imagick not available');
        }

        try {
            $startTime = microtime(true);
            $imagick = new \Imagick($sourcePath);

            $imagick->cropImage(
                $instruction->width,
                $instruction->height,
                $instruction->x,
                $instruction->y,
            );

            $targetPath = $this->getOutputPath($sourcePath, $instruction->format);
            $imagick->setImageFormat($instruction->format);
            $imagick->setImageCompressionQuality($instruction->quality);
            $imagick->writeImage($targetPath);
            $imagick->destroy();

            $duration = (int) ((microtime(true) - $startTime) * 1000);
            return TransformResultDto::success($sourcePath, $targetPath, $duration);
        } catch (\Throwable $e) {
            return TransformResultDto::failure($sourcePath, 'Crop failed: ' . $e->getMessage());
        }
    }

    public function resize(string $sourcePath, ResizeInstructionDto $instruction): TransformResultDto
    {
        if (!$this->available) {
            return TransformResultDto::failure($sourcePath, 'Imagick not available');
        }

        try {
            $startTime = microtime(true);
            $imagick = new \Imagick($sourcePath);

            $this->resizeByMode($imagick, $instruction->width, $instruction->height, $instruction->mode);

            $targetPath = $this->getOutputPath($sourcePath, $instruction->format);
            $imagick->setImageFormat($instruction->format);
            $imagick->setImageCompressionQuality($instruction->quality);
            $imagick->writeImage($targetPath);
            $imagick->destroy();

            $duration = (int) ((microtime(true) - $startTime) * 1000);
            return TransformResultDto::success($sourcePath, $targetPath, $duration);
        } catch (\Throwable $e) {
            return TransformResultDto::failure($sourcePath, 'Resize failed: ' . $e->getMessage());
        }
    }

    public function rotate(string $sourcePath, int $angle): TransformResultDto
    {
        if (!$this->available) {
            return TransformResultDto::failure($sourcePath, 'Imagick not available');
        }

        try {
            $startTime = microtime(true);
            $imagick = new \Imagick($sourcePath);
            $imagick->rotateImage(new \ImagickPixel('transparent'), $angle);

            $targetPath = $this->getOutputPath($sourcePath, 'png');
            $imagick->setImageFormat('png');
            $imagick->writeImage($targetPath);
            $imagick->destroy();

            $duration = (int) ((microtime(true) - $startTime) * 1000);
            return TransformResultDto::success($sourcePath, $targetPath, $duration);
        } catch (\Throwable $e) {
            return TransformResultDto::failure($sourcePath, 'Rotation failed: ' . $e->getMessage());
        }
    }

    public function convertFormat(string $sourcePath, string $format, array $options = []): TransformResultDto
    {
        if (!$this->available) {
            return TransformResultDto::failure($sourcePath, 'Imagick not available');
        }

        try {
            $startTime = microtime(true);
            $imagick = new \Imagick($sourcePath);
            $imagick->setImageFormat(strtoupper($format));

            $quality = (int) ($options['quality'] ?? 85);
            $imagick->setImageCompressionQuality($quality);

            $targetPath = $this->getOutputPath($sourcePath, $format);
            $imagick->writeImage($targetPath);
            $imagick->destroy();

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

    private function resizeByMode(\Imagick $imagick, int $targetWidth, int $targetHeight, string $mode): void
    {
        $currentWidth = $imagick->getImageWidth();
        $currentHeight = $imagick->getImageHeight();

        match ($mode) {
            'cover' => $this->resizeCover($imagick, $currentWidth, $currentHeight, $targetWidth, $targetHeight),
            'contain' => $this->resizeContain($imagick, $targetWidth, $targetHeight),
            'fill' => $imagick->resizeImage($targetWidth, $targetHeight, \Imagick::FILTER_LANCZOS, 1),
            default => $imagick->resizeImage($targetWidth, $targetHeight, \Imagick::FILTER_LANCZOS, 1),
        };
    }

    private function resizeCover(\Imagick $imagick, int $currentWidth, int $currentHeight, int $targetWidth, int $targetHeight): void
    {
        $widthRatio = $targetWidth / $currentWidth;
        $heightRatio = $targetHeight / $currentHeight;
        $scale = max($widthRatio, $heightRatio);

        $newWidth = (int) ($currentWidth * $scale);
        $newHeight = (int) ($currentHeight * $scale);

        $imagick->resizeImage($newWidth, $newHeight, \Imagick::FILTER_LANCZOS, 1);

        $offsetX = (int) (($newWidth - $targetWidth) / 2);
        $offsetY = (int) (($newHeight - $targetHeight) / 2);

        $imagick->cropImage($targetWidth, $targetHeight, $offsetX, $offsetY);
    }

    private function resizeContain(\Imagick $imagick, int $targetWidth, int $targetHeight): void
    {
        $imagick->resizeImage($targetWidth, $targetHeight, \Imagick::FILTER_LANCZOS, 1, true);
    }

    private function extractExif(\Imagick $imagick): ?array
    {
        try {
            $profiles = $imagick->getImageProfiles('exif');
            if (empty($profiles)) {
                return null;
            }
            return json_decode(json_encode($profiles), true);
        } catch (\Throwable) {
            return null;
        }
    }

    private function getOutputPath(string $sourcePath, string $format): string
    {
        $pathInfo = pathinfo($sourcePath);
        $dir = $pathInfo['dirname'];
        $filename = $pathInfo['filename'];
        return "$dir/{$filename}_transformed.$format";
    }
}
