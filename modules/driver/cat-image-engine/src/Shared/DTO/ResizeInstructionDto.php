<?php

declare(strict_types=1);

namespace Modules\CatImageEngine\Shared\DTO;

/**
 * Resize instruction DTO
 */
class ResizeInstructionDto
{
    public function __construct(
        public readonly int $width,
        public readonly int $height,
        public readonly string $mode = 'cover', // cover, contain, fill
        public readonly int $quality = 85,
        public readonly string $format = 'jpeg',
        public readonly ?string $storagePath = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            width: (int) ($data['width'] ?? 0),
            height: (int) ($data['height'] ?? 0),
            mode: $data['mode'] ?? 'cover',
            quality: (int) ($data['quality'] ?? 85),
            format: $data['format'] ?? 'jpeg',
            storagePath: $data['storage_path'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'width' => $this->width,
            'height' => $this->height,
            'mode' => $this->mode,
            'quality' => $this->quality,
            'format' => $this->format,
            'storage_path' => $this->storagePath,
        ];
    }
}
