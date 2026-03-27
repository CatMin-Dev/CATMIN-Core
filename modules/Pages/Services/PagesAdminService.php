<?php

namespace Modules\Pages\Services;

use App\Services\CatminEventBus;
use Illuminate\Support\Str;
use Modules\Logger\Services\SystemLogService;
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

        try {
            app(SystemLogService::class)->logAudit(
                'content.page.created',
                'Page creee',
                [
                    'id' => $page->id,
                    'slug' => $page->slug,
                    'status' => $page->status,
                ],
                'info',
                (string) session('catmin_admin_username', '')
            );
        } catch (\Throwable) {
            // Keep content creation resilient if logging fails.
        }

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

        CatminEventBus::dispatch(CatminEventBus::PAGE_UPDATED, [
            'page' => [
                'id' => $page->id,
                'title' => $page->title,
                'slug' => $page->slug,
                'status' => $page->status,
            ],
        ]);

        try {
            app(SystemLogService::class)->logAudit(
                'content.page.updated',
                'Page modifiee',
                [
                    'id' => $page->id,
                    'slug' => $page->slug,
                    'status' => $page->status,
                ],
                'info',
                (string) session('catmin_admin_username', '')
            );
        } catch (\Throwable) {
            // Keep content update resilient if logging fails.
        }

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

        if ($page->status === 'published') {
            CatminEventBus::dispatch(CatminEventBus::PAGE_PUBLISHED, [
                'page' => [
                    'id' => $page->id,
                    'title' => $page->title,
                    'slug' => $page->slug,
                    'published_at' => optional($page->published_at)->toIso8601String(),
                ],
            ]);
        }

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
