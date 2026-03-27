<?php

namespace Modules\Blog\Services;

use Illuminate\Support\Str;
use Modules\Blog\Models\BlogPost;

class BlogAdminService
{
    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, BlogPost>
     */
    public function listing()
    {
        return BlogPost::query()
            ->orderByDesc('published_at')
            ->orderByDesc('updated_at')
            ->get();
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function create(array $payload): BlogPost
    {
        $slug = $this->uniqueSlug((string) $payload['title'], (string) ($payload['slug'] ?? ''));

        /** @var BlogPost $item */
        $item = BlogPost::query()->create([
            'title' => (string) $payload['title'],
            'slug' => $slug,
            'excerpt' => (string) ($payload['excerpt'] ?? ''),
            'content' => (string) ($payload['content'] ?? ''),
            'status' => (string) ($payload['status'] ?? 'draft'),
            'published_at' => $payload['published_at'] ?: null,
            'media_asset_id' => $payload['media_asset_id'] ?: null,
            'seo_meta_id' => $payload['seo_meta_id'] ?: null,
            // Reserved in V1 to ease future categories/tags linkage.
            'taxonomy_snapshot' => ['category' => null, 'tags' => []],
        ]);

        return $item;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function update(BlogPost $item, array $payload): BlogPost
    {
        $slug = $this->uniqueSlug((string) $payload['title'], (string) ($payload['slug'] ?? ''), $item->id);

        $taxonomySnapshot = is_array($item->taxonomy_snapshot) ? $item->taxonomy_snapshot : ['category' => null, 'tags' => []];

        $item->fill([
            'title' => (string) $payload['title'],
            'slug' => $slug,
            'excerpt' => (string) ($payload['excerpt'] ?? ''),
            'content' => (string) ($payload['content'] ?? ''),
            'status' => (string) ($payload['status'] ?? 'draft'),
            'published_at' => $payload['published_at'] ?: null,
            'media_asset_id' => $payload['media_asset_id'] ?: null,
            'seo_meta_id' => $payload['seo_meta_id'] ?: null,
            'taxonomy_snapshot' => $taxonomySnapshot,
        ]);

        $item->save();

        return $item;
    }

    public function toggleStatus(BlogPost $item): BlogPost
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
        $baseSlug = $baseSlug !== '' ? $baseSlug : 'blog-post';

        $slug = $baseSlug;
        $suffix = 1;

        while (BlogPost::query()->where('slug', $slug)->when($ignoreId !== null, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $suffix++;
            $slug = $baseSlug . '-' . $suffix;
        }

        return $slug;
    }
}
