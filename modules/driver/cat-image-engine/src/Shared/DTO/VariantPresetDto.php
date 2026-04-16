<?php

declare(strict_types=1);

namespace Modules\CatImageEngine\Shared\DTO;

/**
 * Variant preset DTO
 */
class VariantPresetDto
{
    public function __construct(
        public readonly string $name,
        public readonly int $width,
        public readonly int $height,
        public readonly string $mode = 'cover',
        public readonly int $quality = 85,
        public readonly string $format = 'jpeg',
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'] ?? 'unnamed',
            width: (int) ($data['width'] ?? 0),
            height: (int) ($data['height'] ?? 0),
            mode: $data['mode'] ?? 'cover',
            quality: (int) ($data['quality'] ?? 85),
            format: $data['format'] ?? 'jpeg',
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'width' => $this->width,
            'height' => $this->height,
            'mode' => $this->mode,
            'quality' => $this->quality,
            'format' => $this->format,
        ];
    }
}
