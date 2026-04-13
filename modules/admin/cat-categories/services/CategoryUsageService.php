<?php

declare(strict_types=1);

namespace Modules\CatCategories\services;

use Modules\CatCategories\repositories\CategoriesRepository;

final class CategoryUsageService
{
    public function __construct(private readonly CategoriesRepository $repo)
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
