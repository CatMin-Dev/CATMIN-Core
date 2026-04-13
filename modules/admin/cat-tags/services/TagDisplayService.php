<?php

declare(strict_types=1);

namespace Modules\CatTags\services;

final class TagDisplayService
{
    public function asCommaList(array $tags): string
    {
        $names = array_map(static fn (array $t): string => trim((string) ($t['name'] ?? '')), $tags);
        $names = array_values(array_filter($names, static fn (string $n): bool => $n !== ''));
        return implode(', ', $names);
    }

    public function asBadges(array $tags): array
    {
        $out = [];
        foreach ($tags as $tag) {
            $name = trim((string) ($tag['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $out[] = ['label' => $name, 'slug' => (string) ($tag['slug'] ?? '')];
        }
        return $out;
    }
}
