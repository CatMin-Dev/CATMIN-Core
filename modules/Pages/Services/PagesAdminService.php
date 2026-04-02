<?php

namespace Modules\Pages\Services;

use App\Services\CatminEventBus;
use App\Services\Editor\WysiwygSanitizer;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Modules\Logger\Services\SystemLogService;
use Modules\Pages\Models\Page;

class PagesAdminService
{
    public function __construct(private readonly WysiwygSanitizer $sanitizer)
    {
    }

    public function listing(?string $search = null, int $perPage = 25, string $scope = 'active'): LengthAwarePaginator
    {
        $term = trim((string) $search);

        $query = Page::query()
            ->select(['id', 'title', 'slug', 'excerpt', 'status', 'published_at', 'updated_at', 'media_asset_id'])
            ->when($term !== '', function ($query) use ($term) {
                $query->where(function ($inner) use ($term) {
                    $inner->where('title', 'like', '%' . $term . '%')
                        ->orWhere('slug', 'like', '%' . $term . '%')
                        ->orWhere('excerpt', 'like', '%' . $term . '%');
                });
            })
            ->orderByDesc('deleted_at')
            ->orderByDesc('published_at')
            ->orderByDesc('updated_at');

        if ($scope === 'trash') {
            $query->onlyTrashed();
        } elseif ($scope === 'all') {
            $query->withTrashed();
        }

        return $query
            ->paginate($perPage)
            ->withQueryString();
    }

    public function softDelete(Page $page): void
    {
        $page->delete();
    }

    public function restore(Page $page): void
    {
        $page->restore();
    }

    public function hardDelete(Page $page): void
    {
        $page->forceDelete();
    }

    public function emptyTrash(): int
    {
        $trashed = Page::onlyTrashed()->get();
        $count = 0;

        foreach ($trashed as $page) {
            $page->forceDelete();
            $count++;
        }

        return $count;
    }

    public function purgeTrashOlderThan(int $days): int
    {
        $safeDays = max(1, $days);
        $threshold = now()->subDays($safeDays);

        $trashed = Page::onlyTrashed()
            ->where(function (Builder $query) use ($threshold): void {
                $query->whereNotNull('deleted_at')
                    ->where('deleted_at', '<=', $threshold);
            })
            ->get();

        $count = 0;
        foreach ($trashed as $page) {
            $page->forceDelete();
            $count++;
        }

        return $count;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function create(array $payload): Page
    {
        $statusAndDate = $this->resolveStatusAndPublishedAt($payload);
        $slug = $this->uniqueSlug((string) $payload['title'], (string) ($payload['slug'] ?? ''));

        /** @var Page $page */
        $page = Page::query()->create([
            'title'            => (string) $payload['title'],
            'slug'             => $slug,
            'excerpt'          => (string) ($payload['excerpt'] ?? ''),
            'content'          => $this->sanitizer->sanitize((string) ($payload['content'] ?? '')),
            'status'           => $statusAndDate['status'],
            'published_at'     => $statusAndDate['published_at'],
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
        $statusAndDate = $this->resolveStatusAndPublishedAt($payload);
        $slug = $this->uniqueSlug((string) $payload['title'], (string) ($payload['slug'] ?? ''), $page->id);

        $page->fill([
            'title'            => (string) $payload['title'],
            'slug'             => $slug,
            'excerpt'          => (string) ($payload['excerpt'] ?? ''),
            'content'          => $this->sanitizer->sanitize((string) ($payload['content'] ?? '')),
            'status'           => $statusAndDate['status'],
            'published_at'     => $statusAndDate['published_at'],
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
        $nextStatus = in_array($page->status, ['published', 'scheduled'], true) ? 'draft' : 'published';
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
    private function normalizePublishedAt(array $payload): ?Carbon
    {
        $publishedAt = $payload['published_at'] ?? null;

        if ($publishedAt === null || $publishedAt === '') {
            return null;
        }

        return Carbon::parse((string) $publishedAt);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{status:string, published_at:?Carbon}
     */
    private function resolveStatusAndPublishedAt(array $payload): array
    {
        $status = (string) ($payload['status'] ?? 'draft');
        $publishedAt = $this->normalizePublishedAt($payload);

        if ($publishedAt !== null && $publishedAt->isFuture()) {
            $status = 'scheduled';
        }

        if ($status === 'published' && $publishedAt === null) {
            $publishedAt = now();
        }

        if ($status === 'scheduled' && $publishedAt === null) {
            $status = 'draft';
        }

        return [
            'status' => $status,
            'published_at' => $publishedAt,
        ];
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

    public function bulkPublish(array $ids): int
    {
        return Page::whereIn('id', $ids)
            ->whereNotNull('published_at')
            ->update(['status' => 'published']);
    }

    public function bulkUnpublish(array $ids): int
    {
        return Page::whereIn('id', $ids)
            ->update(['published_at' => null, 'status' => 'draft']);
    }

    public function bulkTrash(array $ids): int
    {
        return Page::whereIn('id', $ids)
            ->whereNull('deleted_at')
            ->delete();
    }
}
