<?php

declare(strict_types=1);

namespace Modules\CatCategories\services;

final class CategorySelectorService
{
    public function normalizeSelected(mixed $value): array
    {
        if (is_array($value)) {
            return array_values(array_unique(array_map(static fn ($v): int => max(0, (int) $v), $value)));
        }
        $raw = trim((string) $value);
        if ($raw === '') {
            return [];
        }
        $parts = preg_split('/[\s,]+/u', $raw, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        return array_values(array_unique(array_map(static fn ($v): int => max(0, (int) $v), $parts)));
    }
}
