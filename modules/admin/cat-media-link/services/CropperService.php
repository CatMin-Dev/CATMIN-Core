<?php

declare(strict_types=1);

namespace Modules\CatMediaLink\services;

final class CropperService
{
    public function normalizeCropData(array $input): array
    {
        $x = max(0, (int) ($input['x'] ?? 0));
        $y = max(0, (int) ($input['y'] ?? 0));
        $width = max(1, (int) ($input['width'] ?? 1));
        $height = max(1, (int) ($input['height'] ?? 1));
        $targetWidth = max(1, (int) ($input['target_width'] ?? $width));
        $targetHeight = max(1, (int) ($input['target_height'] ?? $height));
        $rotation = (float) ($input['rotation'] ?? 0);

        return [
            'x' => $x,
            'y' => $y,
            'width' => $width,
            'height' => $height,
            'target_width' => $targetWidth,
            'target_height' => $targetHeight,
            'rotation' => $rotation,
        ];
    }

    public function parseCropJson(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }

        return $this->normalizeCropData($decoded);
    }

    public function exportJson(array $cropData): string
    {
        return (string) json_encode($this->normalizeCropData($cropData), JSON_UNESCAPED_SLASHES);
    }
}
