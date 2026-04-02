<?php

namespace Modules\Articles\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\Articles\Models\ArticleCategory;
use Modules\Articles\Models\Tag;

class ArticleTaxonomyService
{
    /** @return Collection<int, ArticleCategory> */
    public function categories(): Collection
    {
        return ArticleCategory::query()
            ->with('parent')
            ->orderBy('name')
            ->get();
    }

    /** @return Collection<int, array{id:int,name:string}> */
    public function categoriesForSelect(): Collection
    {
        $categories = $this->categories();

        return $categories->map(function (ArticleCategory $category) use ($categories): array {
            return [
                'id' => (int) $category->id,
                'name' => $this->buildCategoryLabel($category, $categories),
            ];
        })->values();
    }

    /** @return Collection<int, Tag> */
    public function tags(): Collection
    {
        return Tag::query()
            ->orderBy('name')
            ->get();
    }

    /** @param array<string, mixed> $payload */
    public function createCategory(array $payload): ArticleCategory
    {
        return ArticleCategory::query()->create([
            'name' => (string) $payload['name'],
            'slug' => $this->uniqueCategorySlug((string) $payload['name'], (string) ($payload['slug'] ?? '')),
            'parent_id' => !empty($payload['parent_id']) ? (int) $payload['parent_id'] : null,
        ]);
    }

    /** @param array<string, mixed> $payload */
    public function updateCategory(ArticleCategory $category, array $payload): ArticleCategory
    {
        $category->fill([
            'name' => (string) $payload['name'],
            'slug' => $this->uniqueCategorySlug((string) $payload['name'], (string) ($payload['slug'] ?? ''), $category->id),
            'parent_id' => !empty($payload['parent_id']) ? (int) $payload['parent_id'] : null,
        ]);
        $category->save();

        return $category;
    }

    public function deleteCategory(ArticleCategory $category): bool
    {
        if ($category->children()->exists() || $category->articles()->exists()) {
            return false;
        }

        $category->delete();

        return true;
    }

    /** @param array<string, mixed> $payload */
    public function createTag(array $payload): Tag
    {
        return Tag::query()->create([
            'name' => (string) $payload['name'],
            'slug' => $this->uniqueTagSlug((string) $payload['name'], (string) ($payload['slug'] ?? '')),
        ]);
    }

    /** @param array<string, mixed> $payload */
    public function updateTag(Tag $tag, array $payload): Tag
    {
        $tag->fill([
            'name' => (string) $payload['name'],
            'slug' => $this->uniqueTagSlug((string) $payload['name'], (string) ($payload['slug'] ?? ''), $tag->id),
        ]);
        $tag->save();

        return $tag;
    }

    public function deleteTag(Tag $tag): bool
    {
        $tag->articles()->detach();
        $tag->delete();

        return true;
    }

    private function uniqueCategorySlug(string $name, string $candidateSlug, ?int $ignoreId = null): string
    {
        return $this->uniqueSlug(ArticleCategory::class, $name, $candidateSlug, $ignoreId);
    }

    private function uniqueTagSlug(string $name, string $candidateSlug, ?int $ignoreId = null): string
    {
        return $this->uniqueSlug(Tag::class, $name, $candidateSlug, $ignoreId);
    }

    /** @param class-string<ArticleCategory|Tag> $modelClass */
    private function uniqueSlug(string $modelClass, string $name, string $candidateSlug, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($candidateSlug !== '' ? $candidateSlug : $name);
        $baseSlug = $baseSlug !== '' ? $baseSlug : 'item';

        $slug = $baseSlug;
        $suffix = 1;

        while ($modelClass::query()->where('slug', $slug)->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))->exists()) {
            $suffix++;
            $slug = $baseSlug . '-' . $suffix;
        }

        return $slug;
    }

    /** @param Collection<int, ArticleCategory> $all */
    private function buildCategoryLabel(ArticleCategory $category, Collection $all): string
    {
        $parts = [$category->name];
        $parentId = $category->parent_id;

        while ($parentId !== null) {
            $parent = $all->firstWhere('id', $parentId);
            if (!$parent instanceof ArticleCategory) {
                break;
            }

            array_unshift($parts, $parent->name);
            $parentId = $parent->parent_id;
        }

        return implode(' / ', $parts);
    }
}