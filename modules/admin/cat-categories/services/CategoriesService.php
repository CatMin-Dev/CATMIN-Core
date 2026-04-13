<?php

declare(strict_types=1);

namespace Modules\CatCategories\services;

use Modules\CatCategories\repositories\CategoriesRepository;

final class CategoriesService
{
    public function __construct(
        private readonly CategoriesRepository $repo,
        private readonly CategoryTreeService $tree,
        private readonly CategoryLinkService $link,
        private readonly CategoryUsageService $usage,
        private readonly CategorySelectorService $selector
    ) {
    }

    public function createCategory(string $name, ?int $parentId = null, int $sortOrder = 0): array
    {
        $name = trim($name);
        if ($name === '') {
            return ['ok' => false, 'message' => 'Nom categorie obligatoire'];
        }
        $slug = $this->slugify($name);
        if ($slug === '') {
            return ['ok' => false, 'message' => 'Slug categorie invalide'];
        }

        if (is_array($this->repo->bySlug($slug))) {
            return ['ok' => false, 'message' => 'Categorie deja existante'];
        }

        $id = $this->repo->createCategory($name, $slug, $parentId, $sortOrder);
        return ['ok' => $id > 0, 'message' => $id > 0 ? 'Categorie creee' : 'Creation categorie impossible'];
    }

    public function dashboard(): array
    {
        $this->usage->refresh();
        $rows = $this->repo->allCategories();
        $tree = $this->tree->build($rows);
        return [
            'stats' => $this->usage->stats(),
            'tree' => $tree,
            'selector' => $this->tree->flattenForSelect($tree),
        ];
    }

    public function syncEntityCategories(string $entityType, int $entityId, mixed $selected): array
    {
        $ids = $this->selector->normalizeSelected($selected);
        return $this->link->syncEntity($entityType, $entityId, $ids);
    }

    public function entityCategoryIds(string $entityType, int $entityId): array
    {
        return $this->link->entityCategoryIds($entityType, $entityId);
    }

    private function slugify(string $input): string
    {
        $value = mb_strtolower(trim($input));
        $value = preg_replace('/[^\p{L}\p{N}\s-]/u', '', $value) ?? $value;
        $value = preg_replace('/[\s_-]+/u', '-', $value) ?? $value;
        $value = trim((string) $value, '-');
        return mb_substr($value, 0, 180);
    }
}
