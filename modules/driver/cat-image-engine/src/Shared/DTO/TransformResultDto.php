<?php

declare(strict_types=1);

namespace Modules\CatImageEngine\Shared\DTO;

/**
 * Transform result DTO
 */
class TransformResultDto
{
    public function __construct(
        public readonly bool $success,
        public readonly string $originalPath,
        public readonly ?string $transformPath = null,
        public readonly ?string $errorMessage = null,
        public readonly ?int $durationMs = null,
    ) {}

    public static function success(string $originalPath, string $transformPath, ?int $durationMs = null): self
    {
        return new self(
            success: true,
            originalPath: $originalPath,
            transformPath: $transformPath,
            errorMessage: null,
            durationMs: $durationMs,
        );
    }

    public static function failure(string $originalPath, string $errorMessage, ?int $durationMs = null): self
    {
        return new self(
            success: false,
            originalPath: $originalPath,
            transformPath: null,
            errorMessage: $errorMessage,
            durationMs: $durationMs,
        );
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'original_path' => $this->originalPath,
            'transform_path' => $this->transformPath,
            'error_message' => $this->errorMessage,
            'duration_ms' => $this->durationMs,
        ];
    }
}
