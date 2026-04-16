<?php

declare(strict_types=1);

namespace Modules\CatImageEngine\Support;

use Modules\CatImageEngine\Shared\Contracts\ImageProcessorInterface;
use Modules\CatImageEngine\Support\Drivers\ImagickImageProcessor;
use Modules\CatImageEngine\Support\Drivers\BasicImageProcessor;

/**
 * Image processor resolver
 */
final class ImageProcessorResolver
{
    private static ?ImageProcessorInterface $instance = null;

    public static function resolve(): ImageProcessorInterface
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $settings = config('image_engine', []);
        $driver = strtolower(trim((string) ($settings['driver'] ?? 'imagick')));
        $allowFallback = !empty($settings['fallback_allowed'] ?? true);

        // Try primary driver choice
        $processor = match ($driver) {
            'imagick' => new ImagickImageProcessor(),
            'gd', 'basic' => new BasicImageProcessor(),
            default => new ImagickImageProcessor(), // default to Imagick
        };

        // Check availability and apply fallback policy
        if (!$processor->isAvailable()) {
            if (!$allowFallback) {
                throw new \RuntimeException("Requested driver '{$driver}' is not available and fallback is disabled");
            }

            // Try fallback
            $fallback = new BasicImageProcessor();
            if ($fallback->isAvailable()) {
                $processor = $fallback;
            } else {
                throw new \RuntimeException('No image processor available (Imagick and GD both unavailable)');
            }
        }

        self::$instance = $processor;
        return self::$instance;
    }

    public static function reset(): void
    {
        self::$instance = null;
    }
}
