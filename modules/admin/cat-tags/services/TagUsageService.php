<?php

declare(strict_types=1);

namespace Modules\CatTags\services;

use Modules\CatTags\repositories\TagsRepository;

final class TagUsageService
{
    public function __construct(private readonly TagsRepository $repo)
    {
    }

    public function refresh(): void
    {
        $this->repo->refreshUsageCount();
    }

    public function stats(): array
    {
        return $this->repo->stats();
    }
}
