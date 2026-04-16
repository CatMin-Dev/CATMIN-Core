<?php

declare(strict_types=1);

namespace Modules\CatImageEngine\Shared\Services;

use Modules\CatImageEngine\Support\ImageProcessorResolver;
use Modules\CatImageEngine\Shared\DTO\VariantPresetDto;
use Modules\CatImageEngine\Shared\DTO\TransformResultDto;

/**
 * Image variant service
 */
final class ImageVariantService
{
    /**
     * Generate image variant from preset
     */
    public function generateVariant(string $sourcePath, VariantPresetDto $preset): TransformResultDto
    {
        $processor = ImageProcessorResolver::resolve();
        return $processor->generateVariant($sourcePath, $preset);
    }

    /**
     * Generate multiple variants from presets
     */
    public function generateVariants(string $sourcePath, array $presets): array
    {
        $results = [];
        foreach ($presets as $preset) {
            if ($preset instanceof VariantPresetDto) {
                $results[$preset->name] = $this->generateVariant($sourcePath, $preset);
            }
        }
        return $results;
    }
}
