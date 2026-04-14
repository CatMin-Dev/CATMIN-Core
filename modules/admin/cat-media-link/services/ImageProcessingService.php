<?php

declare(strict_types=1);

namespace Modules\CatMediaLink\services;

use RuntimeException;

final class ImageProcessingService
{
    public function validateImage(string $path): bool
    {
        if ($path === '' || !is_file($path)) {
            return false;
        }
        if (!extension_loaded('imagick')) {
            return false;
        }

        try {
            $img = new \Imagick($path);
            $ok = $img->getImageWidth() > 0 && $img->getImageHeight() > 0;
            $img->clear();
            $img->destroy();
            return $ok;
        } catch (\Throwable) {
            return false;
        }
    }

    public function resize(string $sourcePath, string $outputPath, int $width, int $height, string $mode = 'fit', int $quality = 82, string $format = 'jpg'): array
    {
        $this->assertRuntime($sourcePath);

        $img = new \Imagick($sourcePath);
        $img->setImageOrientation(\Imagick::ORIENTATION_TOPLEFT);
        $srcW = (int) $img->getImageWidth();
        $srcH = (int) $img->getImageHeight();
        $targetW = max(1, $width);
        $targetH = max(1, $height);
        $mode = strtolower(trim($mode));

        if ($mode === 'cover') {
            $srcRatio = $srcW / max(1, $srcH);
            $targetRatio = $targetW / max(1, $targetH);
            if ($srcRatio > $targetRatio) {
                $newHeight = $targetH;
                $newWidth = (int) round($targetH * $srcRatio);
            } else {
                $newWidth = $targetW;
                $newHeight = (int) round($targetW / max(0.001, $srcRatio));
            }
            $img->resizeImage($newWidth, $newHeight, \Imagick::FILTER_LANCZOS, 1.0, true);
            $x = max(0, (int) floor(($newWidth - $targetW) / 2));
            $y = max(0, (int) floor(($newHeight - $targetH) / 2));
            $img->cropImage($targetW, $targetH, $x, $y);
        } elseif ($mode === 'contain') {
            $img->thumbnailImage($targetW, $targetH, true, true);
            $canvas = new \Imagick();
            $canvas->newImage($targetW, $targetH, new \ImagickPixel('transparent'));
            $canvas->setImageFormat($this->normalizeFormat($format));
            $x = max(0, (int) floor(($targetW - $img->getImageWidth()) / 2));
            $y = max(0, (int) floor(($targetH - $img->getImageHeight()) / 2));
            $canvas->compositeImage($img, \Imagick::COMPOSITE_OVER, $x, $y);
            $img->clear();
            $img->destroy();
            $img = $canvas;
        } else {
            $img->thumbnailImage($targetW, $targetH, true, true);
            $targetW = (int) $img->getImageWidth();
            $targetH = (int) $img->getImageHeight();
        }

        $format = $this->normalizeFormat($format);
        $img->setImageFormat($format);
        $img->setImageCompressionQuality(max(1, min(100, $quality)));
        $this->ensureOutputDirectory($outputPath);
        $ok = $img->writeImage($outputPath);
        $outW = (int) $img->getImageWidth();
        $outH = (int) $img->getImageHeight();
        $img->clear();
        $img->destroy();

        if (!$ok) {
            throw new RuntimeException('Unable to write variant file.');
        }

        return ['width' => $outW, 'height' => $outH];
    }

    public function crop(string $sourcePath, string $outputPath, array $cropData, int $quality = 82, string $format = 'jpg'): array
    {
        $this->assertRuntime($sourcePath);
        $img = new \Imagick($sourcePath);

        $x = max(0, (int) ($cropData['x'] ?? 0));
        $y = max(0, (int) ($cropData['y'] ?? 0));
        $w = max(1, (int) ($cropData['width'] ?? 1));
        $h = max(1, (int) ($cropData['height'] ?? 1));
        $targetW = max(1, (int) ($cropData['target_width'] ?? $w));
        $targetH = max(1, (int) ($cropData['target_height'] ?? $h));

        $img->cropImage($w, $h, $x, $y);
        $img->resizeImage($targetW, $targetH, \Imagick::FILTER_LANCZOS, 1.0, true);
        $img->setImageFormat($this->normalizeFormat($format));
        $img->setImageCompressionQuality(max(1, min(100, $quality)));

        $this->ensureOutputDirectory($outputPath);
        $ok = $img->writeImage($outputPath);
        $outW = (int) $img->getImageWidth();
        $outH = (int) $img->getImageHeight();
        $img->clear();
        $img->destroy();

        if (!$ok) {
            throw new RuntimeException('Unable to write cropped variant file.');
        }

        return ['width' => $outW, 'height' => $outH];
    }

    public function generateVariant(array $asset, array $preset, ?array $cropData = null, string $generatedBy = 'auto'): array
    {
        $storagePath = trim((string) ($asset['storage_path'] ?? ''));
        if ($storagePath === '') {
            return ['ok' => false, 'message' => 'Asset has no local storage path.'];
        }

        $sourceAbsolute = CATMIN_PUBLIC . $storagePath;
        $presetKey = trim((string) ($preset['preset_key'] ?? 'custom'));
        $format = $this->normalizeFormat((string) ($preset['format'] ?? 'jpg'));
        $variantRelativeDir = '/uploads/media/variants/' . (int) ($asset['id'] ?? 0);
        $variantRelative = $variantRelativeDir . '/' . $presetKey . '.' . $format;
        $variantAbsolute = CATMIN_PUBLIC . $variantRelative;
        $quality = max(1, min(100, (int) ($preset['quality'] ?? 82)));

        if (is_array($cropData) && $cropData !== []) {
            $meta = $this->crop($sourceAbsolute, $variantAbsolute, $cropData, $quality, $format);
        } else {
            $meta = $this->resize(
                $sourceAbsolute,
                $variantAbsolute,
                (int) ($preset['width'] ?? 1),
                (int) ($preset['height'] ?? 1),
                (string) ($preset['crop_mode'] ?? 'cover'),
                $quality,
                $format
            );
        }

        return [
            'ok' => true,
            'payload' => [
                'media_id' => (int) ($asset['id'] ?? 0),
                'preset_key' => $presetKey,
                'file_path' => $variantRelative,
                'width' => (int) ($meta['width'] ?? (int) ($preset['width'] ?? 1)),
                'height' => (int) ($meta['height'] ?? (int) ($preset['height'] ?? 1)),
                'crop_data' => json_encode($cropData ?? ['mode' => 'preset'], JSON_UNESCAPED_SLASHES),
                'generated_by' => $generatedBy,
            ],
        ];
    }

    public function regenerateAllVariants(array $asset, array $presets): array
    {
        $results = [];
        foreach ($presets as $preset) {
            try {
                $results[] = $this->generateVariant($asset, $preset, null, 'auto');
            } catch (\Throwable $e) {
                $results[] = ['ok' => false, 'message' => $e->getMessage(), 'preset_key' => (string) ($preset['preset_key'] ?? '')];
            }
        }
        return $results;
    }

    private function assertRuntime(string $sourcePath): void
    {
        if (!extension_loaded('imagick')) {
            throw new RuntimeException('Imagick extension is required for variant generation.');
        }
        if ($sourcePath === '' || !is_file($sourcePath)) {
            throw new RuntimeException('Invalid source image path.');
        }
    }

    private function normalizeFormat(string $format): string
    {
        $format = strtolower(trim($format));
        return match ($format) {
            'jpg', 'jpeg' => 'jpeg',
            'webp' => 'webp',
            'png' => 'png',
            default => 'jpeg',
        };
    }

    private function ensureOutputDirectory(string $outputPath): void
    {
        $dir = dirname($outputPath);
        if ($dir !== '' && !is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException('Unable to create variant directory.');
        }
    }
}
