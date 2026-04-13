<?php

declare(strict_types=1);

namespace Modules\CatTags\services;

use Modules\CatTags\repositories\TagsRepository;

final class TagAutocompleteService
{
    public function __construct(private readonly TagsRepository $repo)
    {
    }

    public function suggest(string $query, int $limit = 12): array
    {
        $query = trim($query);
        if ($query === '') {
            return [];
        }

        $rows = $this->repo->searchTags($query, $limit);
        return array_map(static fn (array $row): array => [
            'id' => (int) ($row['id'] ?? 0),
            'name' => (string) ($row['name'] ?? ''),
            'slug' => (string) ($row['slug'] ?? ''),
            'usage_count' => (int) ($row['usage_count'] ?? 0),
        ], $rows);
    }
}
