<?php

declare(strict_types=1);

namespace Modules\CatCategories\services;

use Modules\CatCategories\repositories\CategoriesRepository;

final class CategoryLinkService
{
    public function __construct(private readonly CategoriesRepository $repo)
    {
    }

    public function syncEntity(string $entityType, int $entityId, array $categoryIds): array
    {
        $entityType = strtolower(trim($entityType));
        if ($entityType === '' || $entityId <= 0) {
            return ['ok' => false, 'message' => 'entity_type/entity_id invalides'];
        }

        $ids = [];
        foreach ($categoryIds as $id) {
            $n = (int) $id;
            if ($n > 0) {
                $ids[] = $n;
            }
        }
        $ids = array_values(array_unique($ids));

        $this->repo->unlinkEntity($entityType, $entityId);
        foreach ($ids as $id) {
            $this->repo->linkCategory($id, $entityType, $entityId);
        }
        $this->repo->refreshUsageCount();

        return ['ok' => true, 'message' => 'Categories synchronisees'];
    }

    public function entityCategoryIds(string $entityType, int $entityId): array
    {
        return $this->repo->entityCategoryIds(strtolower(trim($entityType)), $entityId);
    }
}
