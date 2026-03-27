<?php

namespace Modules\Articles\Services;

use Illuminate\Support\Str;
use Modules\Articles\Models\Article;

class ArticleAdminService
{
    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Article>
     */
    public function listing()
    {
        return Article::query()
            ->orderByDesc('published_at')
            ->orderByDesc('updated_at')
            ->get();
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function create(array $payload): Article
    {
        $slug = $this->uniqueSlug((string) $payload['title'], (string) ($payload['slug'] ?? ''));

        /** @var Article $item */
        $item = Article::query()->create([
            'title' => (string) $payload['title'],
            'slug' => $slug,
            'excerpt' => (string) ($payload['excerpt'] ?? ''),
            'content' => (string) ($payload['content'] ?? ''),
            'content_type' => (string) ($payload['content_type'] ?? 'article'),
            'status' => (string) ($payload['status'] ?? 'draft'),
            'published_at' => $payload['published_at'] ?: null,
            'media_asset_id' => $payload['media_asset_id'] ?: null,
            'seo_meta_id' => $payload['seo_meta_id'] ?: null,
            'taxonomy_snapshot' => ['category' => null, 'tags' => []],
        ]);

        return $item;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function update(Article $item, array $payload): Article
    {
        $slug = $this->uniqueSlug((string) $payload['title'], (string) ($payload['slug'] ?? ''), $item->id);

        $taxonomySnapshot = is_array($item->taxonomy_snapshot) ? $item->taxonomy_snapshot : ['category' => null, 'tags' => []];

        $item->fill([
            'title' => (string) $payload['title'],
            'slug' => $slug,
            'excerpt' => (string) ($payload['excerpt'] ?? ''),
            'content' => (string) ($payload['content'] ?? ''),
            'content_type' => (string) ($payload['content_type'] ?? $item->content_type),
            'status' => (string) ($payload['status'] ?? 'draft'),
            'published_at' => $payload['published_at'] ?: null,
            'media_asset_id' => $payload['media_asset_id'] ?: null,
            'seo_meta_id' => $payload['seo_meta_id'] ?: null,
            'taxonomy_snapshot' => $taxonomySnapshot,
        ]);

        $item->save();

        return $item;
    }

    public function toggleStatus(Article $item): Article
    {
        $next = $item->status === 'published' ? 'draft' : 'published';
        $item->status = $next;

        if ($next === 'published' && $item->published_at === null) {
            $item->published_at = now();
        }

        $item->save();

        return $item;
    }

    private function uniqueSlug(string $title, string $candidateSlug, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($candidateSlug !== '' ? $candidateSlug : $title);
        $baseSlug = $baseSlug !== '' ? $baseSlug : 'article';

        $slug = $baseSlug;
        $suffix = 1;

        while (Article::query()->where('slug', $slug)->when($ignoreId !== null, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $suffix++;
            $slug = $baseSlug . '-' . $suffix;
        }

        return $slug;
    }
}
