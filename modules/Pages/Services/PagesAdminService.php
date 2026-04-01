<?php

namespace Modules\Pages\Services;

use App\Services\CatminEventBus;
use App\Services\Editor\WysiwygSanitizer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Modules\Logger\Services\SystemLogService;
use Modules\Pages\Models\Page;

class PagesAdminService
{
    public function __construct(private readonly WysiwygSanitizer $sanitizer)
    {
    }

    public function listing(?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        $term = trim((string) $search);

        return Page::query()
            ->select(['id', 'title', 'slug', 'excerpt', 'status', 'published_at', 'updated_at', 'media_asset_id'])
            ->when($term !== '', function ($query) use ($term) {
                $query->where(function ($inner) use ($term) {
                    $inner->where('title', 'like', '%' . $term . '%')
                        ->orWhere('slug', 'like', '%' . $term . '%')
                        ->orWhere('excerpt', 'like', '%' . $term . '%');
                });
            })
            ->orderByDesc('published_at')
            ->orderByDesc('updated_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function create(array $payload): Page
    {
        $slug = $this->uniqueSlug((string) $payload['title'], (string) ($payload['slug'] ?? ''));

        /** @var Page $page */
        $page = Page::query()->create([
            'title'            => (string) $payload['title'],
            'slug'             => $slug,
            'excerpt'          => (string) ($payload['excerpt'] ?? ''),
            'content'          => $this->sanitizer->sanitize((string) ($payload['content'] ?? '')),
            'status'           => (string) ($payload['status'] ?? 'draft'),
            'published_at'     => $this->normalizePublishedAt($payload),
            'media_asset_id'   => $payload['media_asset_id'] ?: null,
            'meta_title'       => (string) ($payload['meta_title'] ?? ''),
            'meta_description' => (string) ($payload['meta_description'] ?? ''),
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
            'title'            => (string) $payload['title'],
            'slug'             => $slug,
            'excerpt'          => (string) ($payload['excerpt'] ?? ''),
            'content'          => $this->sanitizer->sanitize((string) ($payload['content'] ?? '')),
            'status'           => (string) ($payload['status'] ?? 'draft'),
            'published_at'     => $this->normalizePublishedAt($payload),
            'media_asset_id'   => $payload['media_asset_id'] ?: null,
            'meta_title'       => (string) ($payload['meta_title'] ?? ''),
            'meta_description' => (string) ($payload['meta_description'] ?? ''),
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
