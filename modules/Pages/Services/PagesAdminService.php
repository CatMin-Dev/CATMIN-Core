<?php

namespace Modules\Pages\Services;

use App\Services\CatminEventBus;
use Illuminate\Support\Str;
use Modules\Pages\Models\Page;

class PagesAdminService
{
    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Page>
     */
    public function listing()
    {
        return Page::query()
            ->orderByDesc('published_at')
            ->orderByDesc('updated_at')
            ->get();
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function create(array $payload): Page
    {
        $slug = $this->uniqueSlug((string) $payload['title'], (string) ($payload['slug'] ?? ''));

        /** @var Page $page */
        $page = Page::query()->create([
            'title' => (string) $payload['title'],
            'slug' => $slug,
            'content' => (string) ($payload['content'] ?? ''),
            'status' => (string) ($payload['status'] ?? 'draft'),
            'published_at' => $this->normalizePublishedAt($payload),
        ]);

        CatminEventBus::dispatch(CatminEventBus::CONTENT_CREATED, [
            'content' => [
                'type' => 'page',
                'id' => $page->id,
                'title' => $page->title,
                'slug' => $page->slug,
                'status' => $page->status,
            ],
        ]);

        return $page;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function update(Page $page, array $payload): Page
    {
        $slug = $this->uniqueSlug((string) $payload['title'], (string) ($payload['slug'] ?? ''), $page->id);

        $page->fill([
            'title' => (string) $payload['title'],
            'slug' => $slug,
            'content' => (string) ($payload['content'] ?? ''),
            'status' => (string) ($payload['status'] ?? 'draft'),
            'published_at' => $this->normalizePublishedAt($payload),
        ]);

        $page->save();

        CatminEventBus::dispatch(CatminEventBus::CONTENT_UPDATED, [
            'content' => [
                'type' => 'page',
                'id' => $page->id,
                'title' => $page->title,
                'slug' => $page->slug,
                'status' => $page->status,
            ],
        ]);

        return $page;
    }

    public function toggleStatus(Page $page): Page
    {
        $nextStatus = $page->status === 'published' ? 'draft' : 'published';
        $page->status = $nextStatus;

        if ($nextStatus === 'published' && $page->published_at === null) {
            $page->published_at = now();
        }

        $page->save();

        return $page;
    }

    /**
     * Lightweight helper base for future frontend URL helpers.
     */
    public function publicPath(Page $page): string
    {
        return '/page/' . ltrim($page->slug, '/');
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function normalizePublishedAt(array $payload): ?string
    {
        $publishedAt = $payload['published_at'] ?? null;

        if ($publishedAt === null || $publishedAt === '') {
            return null;
        }

        return (string) $publishedAt;
    }

    private function uniqueSlug(string $title, string $candidateSlug, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($candidateSlug !== '' ? $candidateSlug : $title);
        $baseSlug = $baseSlug !== '' ? $baseSlug : 'page';

        $slug = $baseSlug;
        $suffix = 1;

        while ($this->slugExists($slug, $ignoreId)) {
            $suffix++;
            $slug = $baseSlug . '-' . $suffix;
        }

        return $slug;
    }

    private function slugExists(string $slug, ?int $ignoreId = null): bool
    {
        return Page::query()
            ->where('slug', $slug)
            ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists();
    }
}
