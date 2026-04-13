<?php

declare(strict_types=1);

namespace Modules\CatTags\services;

use Modules\CatTags\repositories\TagsRepository;

final class TagsService
{
    public function __construct(
        private readonly TagsRepository $repo,
        private readonly TagAutocompleteService $autocomplete,
        private readonly TagLinkService $linker,
        private readonly TagUsageService $usage,
        private readonly TagDisplayService $display
    ) {
    }

    public function dashboard(string $query = ''): array
    {
        $this->usage->refresh();
        return [
            'stats' => $this->usage->stats(),
            'tags' => $this->repo->allTags($query, 150),
        ];
    }

    public function suggest(string $query): array
    {
        return $this->autocomplete->suggest($query, 12);
    }

    public function syncEntity(string $entityType, int $entityId, string $tagsCsv): array
    {
        $raw = preg_split('/[\s,]+/u', $tagsCsv, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        return $this->linker->syncEntityTags($entityType, $entityId, $raw);
    }

    public function entityTagsCsv(string $entityType, int $entityId): string
    {
        return $this->display->asCommaList($this->linker->entityTags($entityType, $entityId));
    }
}
