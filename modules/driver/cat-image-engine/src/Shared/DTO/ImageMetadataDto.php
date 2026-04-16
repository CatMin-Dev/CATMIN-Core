<?php

declare(strict_types=1);

namespace Modules\CatImageEngine\Shared\DTO;

/**
 * Image metadata DTO
 */
class ImageMetadataDto
{
    public function __construct(
        public readonly string $path,
        public readonly int $width,
        public readonly int $height,
        public readonly string $format,
        public readonly string $mimeType,
        public readonly int $filesize,
        public readonly ?string $colorspace = null,
        public readonly ?string $compression = null,
        public readonly ?array $exif = null,
        public readonly ?int $dpi = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            path: $data['path'] ?? '',
            width: (int) ($data['width'] ?? 0),
            height: (int) ($data['height'] ?? 0),
            format: $data['format'] ?? 'unknown',
            mimeType: $data['mime_type'] ?? 'application/octet-stream',
            filesize: (int) ($data['filesize'] ?? 0),
            colorspace: $data['colorspace'] ?? null,
            compression: $data['compression'] ?? null,
            exif: $data['exif'] ?? null,
            dpi: isset($data['dpi']) ? (int) $data['dpi'] : null,
        );
    }

    public function toArray(): array
    {
        return [
            'path' => $this->path,
            'width' => $this->width,
            'height' => $this->height,
            'format' => $this->format,
            'mime_type' => $this->mimeType,
            'filesize' => $this->filesize,
            'colorspace' => $this->colorspace,
            'compression' => $this->compression,
            'exif' => $this->exif,
            'dpi' => $this->dpi,
        ];
    }
}
