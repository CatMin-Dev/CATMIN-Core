<?php

declare(strict_types=1);

namespace Modules\CatImageEngine\Shared\DTO;

/**
 * Crop instruction DTO
 */
class CropInstructionDto
{
    public function __construct(
        public readonly int $x,
        public readonly int $y,
        public readonly int $width,
        public readonly int $height,
        public readonly string $format = 'jpeg',
        public readonly int $quality = 85,
        public readonly ?string $storagePath = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            x: (int) ($data['x'] ?? 0),
            y: (int) ($data['y'] ?? 0),
            width: (int) ($data['width'] ?? 0),
            height: (int) ($data['height'] ?? 0),
            format: $data['format'] ?? 'jpeg',
            quality: (int) ($data['quality'] ?? 85),
            storagePath: $data['storage_path'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'x' => $this->x,
            'y' => $this->y,
            'width' => $this->width,
            'height' => $this->height,
            'format' => $this->format,
            'quality' => $this->quality,
            'storage_path' => $this->storagePath,
        ];
    }
}
