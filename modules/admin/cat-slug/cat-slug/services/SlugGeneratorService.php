<?php

declare(strict_types=1);

namespace Modules\CatSlug\services;

final class SlugGeneratorService
{
    public function __construct(private readonly SlugNormalizerService $normalizer)
    {
    }

    public function generate(string $sourceText, int $maxLength = 96, string $separator = '-', bool $lowercaseOnly = true, bool $transliterationEnabled = true): string
    {
        $maxLength = max(8, min(191, $maxLength));
        $slug = $this->normalizer->normalize($sourceText, $separator, $lowercaseOnly, $transliterationEnabled);
        if ($slug === '') {
            return '';
        }
        return substr($slug, 0, $maxLength);
    }
}
