<?php

namespace Modules\News\Services;

use Illuminate\Support\Str;
use Modules\News\Models\NewsItem;

class NewsAdminService
{
    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, NewsItem>
     */
    public function listing()
    {
        return NewsItem::query()
            ->orderByDesc('published_at')
            ->orderByDesc('updated_at')
            ->get();
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function create(array $payload): NewsItem
    {
        $slug = $this->uniqueSlug((string) $payload['title'], (string) ($payload['slug'] ?? ''));

        /** @var NewsItem $item */
        $item = NewsItem::query()->create([
            'title' => (string) $payload['title'],
            'slug' => $slug,
            'summary' => (string) ($payload['summary'] ?? ''),
            'content' => (string) ($payload['content'] ?? ''),
            'status' => (string) ($payload['status'] ?? 'draft'),
            'published_at' => $payload['published_at'] ?: null,
            'media_asset_id' => $payload['media_asset_id'] ?: null,
            'seo_meta_id' => $payload['seo_meta_id'] ?: null,
        ]);

        return $item;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function update(NewsItem $item, array $payload): NewsItem
    {
        $slug = $this->uniqueSlug((string) $payload['title'], (string) ($payload['slug'] ?? ''), $item->id);

        $item->fill([
            'title' => (string) $payload['title'],
            'slug' => $slug,
            'summary' => (string) ($payload['summary'] ?? ''),
            'content' => (string) ($payload['content'] ?? ''),
            'status' => (string) ($payload['status'] ?? 'draft'),
            'published_at' => $payload['published_at'] ?: null,
            'media_asset_id' => $payload['media_asset_id'] ?: null,
            'seo_meta_id' => $payload['seo_meta_id'] ?: null,
        ]);

        $item->save();

        return $item;
    }

    public function toggleStatus(NewsItem $item): NewsItem
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
        $baseSlug = $baseSlug !== '' ? $baseSlug : 'news';

        $slug = $baseSlug;
        $suffix = 1;

        while (NewsItem::query()->where('slug', $slug)->when($ignoreId !== null, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $suffix++;
            $slug = $baseSlug . '-' . $suffix;
        }

        return $slug;
    }
}
