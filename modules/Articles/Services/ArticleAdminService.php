<?php

namespace Modules\Articles\Services;

use App\Services\Analytics;
use App\Services\CatminEventBus;
use App\Services\Editor\WysiwygSanitizer;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Modules\Cache\Services\QueryCacheService;
use Modules\Logger\Services\SystemLogService;
use Modules\Articles\Models\Article;
use Modules\Articles\Models\Tag;

class ArticleAdminService
{
    public function __construct(private readonly WysiwygSanitizer $sanitizer)
    {
    }

    public function listing(?string $search = null, int $perPage = 25, string $scope = 'active', ?int $categoryId = null, ?int $tagId = null): LengthAwarePaginator
    {
        $term = trim((string) $search);
        $page = max(1, (int) request()->query('page', 1));
        $key = 'listing.' . md5(json_encode([$term, $perPage, $scope, $categoryId, $tagId, $page]));

        return QueryCacheService::remember('articles', $key, 90, function () use ($term, $perPage, $scope, $categoryId, $tagId): LengthAwarePaginator {
            $query = Article::query()
                ->with(['category:id,name', 'tags:id,name'])
                ->select(['id', 'title', 'slug', 'excerpt', 'content_type', 'article_category_id', 'status', 'published_at', 'updated_at', 'media_asset_id'])
                ->when($term !== '', function ($query) use ($term) {
                    $query->where(function ($inner) use ($term) {
                        $inner->where('title', 'like', '%' . $term . '%')
                            ->orWhere('slug', 'like', '%' . $term . '%')
                            ->orWhere('excerpt', 'like', '%' . $term . '%')
                            ->orWhere('content_type', 'like', '%' . $term . '%');
                    });
                })
                ->when($categoryId !== null, fn ($query) => $query->where('article_category_id', $categoryId))
                ->when($tagId !== null, fn ($query) => $query->whereHas('tags', fn ($inner) => $inner->where('tags.id', $tagId)))
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
        });
    }

    public function softDelete(Article $item): void
    {
        $item->delete();
        $this->invalidateCache();
    }

    public function restore(Article $item): void
    {
        $item->restore();
        $this->invalidateCache();
    }

    public function hardDelete(Article $item): void
    {
        $item->forceDelete();
        $this->invalidateCache();
    }

    public function emptyTrash(): int
    {
        $trashed = Article::onlyTrashed()->get();
        $count = 0;

        foreach ($trashed as $item) {
            $item->forceDelete();
            $count++;
        }

        $this->invalidateCache();

        return $count;
    }

    public function purgeTrashOlderThan(int $days): int
    {
        $safeDays = max(1, $days);
        $threshold = now()->subDays($safeDays);

        $trashed = Article::onlyTrashed()
            ->where(function (Builder $query) use ($threshold): void {
                $query->whereNotNull('deleted_at')
                    ->where('deleted_at', '<=', $threshold);
            })
            ->get();

        $count = 0;
        foreach ($trashed as $item) {
            $item->forceDelete();
            $count++;
        }

        if ($count > 0) {
            $this->invalidateCache();
        }

        return $count;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function create(array $payload): Article
    {
        $statusAndDate = $this->resolveStatusAndPublishedAt($payload);
        $slug = $this->uniqueSlug((string) $payload['title'], (string) ($payload['slug'] ?? ''));

        /** @var Article $item */
        $item = Article::query()->create([
            'title'            => (string) $payload['title'],
            'slug'             => $slug,
            'excerpt'          => (string) ($payload['excerpt'] ?? ''),
            'content'          => $this->sanitizer->sanitize((string) ($payload['content'] ?? '')),
            'content_type'     => (string) ($payload['content_type'] ?? 'article'),
            'article_category_id' => !empty($payload['article_category_id']) ? (int) $payload['article_category_id'] : null,
            'status'           => $statusAndDate['status'],
            'published_at'     => $statusAndDate['published_at'],
            'media_asset_id'   => ($payload['media_asset_id'] ?? null) ?: null,
            'seo_meta_id'      => ($payload['seo_meta_id'] ?? null) ?: null,
            'meta_title'       => (string) ($payload['meta_title'] ?? ''),
            'meta_description' => (string) ($payload['meta_description'] ?? ''),
            'taxonomy_snapshot'=> [
                'category' => null,
                'tags' => [],
            ],
        ]);

        $item->tags()->sync($this->normalizeTagIds($payload['tag_ids'] ?? []));

        CatminEventBus::dispatch(CatminEventBus::CONTENT_CREATED, [
            'content' => [
                'type' => 'article',
                'id' => $item->id,
                'title' => $item->title,
                'slug' => $item->slug,
                'status' => $item->status,
            ],
        ]);
        Analytics::track('article.created', 'content', 'create', 'success', [
            'status' => (string) $item->status,
        ]);

        try {
            app(SystemLogService::class)->logAudit(
                'content.article.created',
                'Article cree',
                [
                    'id' => $item->id,
                    'slug' => $item->slug,
                    'status' => $item->status,
                    'content_type' => $item->content_type,
                ],
                'info',
                (string) session('catmin_admin_username', '')
            );
        } catch (\Throwable) {
            // Keep content creation resilient if logging fails.
        }

        $this->invalidateCache();

        return $item;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function update(Article $item, array $payload): Article
    {
        $statusAndDate = $this->resolveStatusAndPublishedAt($payload);
        $slug = $this->uniqueSlug((string) $payload['title'], (string) ($payload['slug'] ?? ''), $item->id);

        $taxonomySnapshot = is_array($item->taxonomy_snapshot) ? $item->taxonomy_snapshot : ['category' => null, 'tags' => []];

        $item->fill([
            'title'            => (string) $payload['title'],
            'slug'             => $slug,
            'excerpt'          => (string) ($payload['excerpt'] ?? ''),
            'content'          => $this->sanitizer->sanitize((string) ($payload['content'] ?? '')),
            'content_type'     => (string) ($payload['content_type'] ?? $item->content_type),
            'article_category_id' => !empty($payload['article_category_id']) ? (int) $payload['article_category_id'] : null,
            'status'           => $statusAndDate['status'],
            'published_at'     => $statusAndDate['published_at'],
            'media_asset_id'   => ($payload['media_asset_id'] ?? null) ?: null,
            'seo_meta_id'      => ($payload['seo_meta_id'] ?? null) ?: null,
            'meta_title'       => (string) ($payload['meta_title'] ?? ''),
            'meta_description' => (string) ($payload['meta_description'] ?? ''),
            'taxonomy_snapshot'=> [
                'category' => null,
                'tags' => [],
            ],
        ]);

        $item->save();
        $item->tags()->sync($this->normalizeTagIds($payload['tag_ids'] ?? []));

        CatminEventBus::dispatch(CatminEventBus::CONTENT_UPDATED, [
            'content' => [
                'type' => 'article',
                'id' => $item->id,
                'title' => $item->title,
                'slug' => $item->slug,
                'status' => $item->status,
            ],
        ]);

        CatminEventBus::dispatch(CatminEventBus::ARTICLE_UPDATED, [
            'article' => [
                'id' => $item->id,
                'title' => $item->title,
                'slug' => $item->slug,
                'status' => $item->status,
            ],
        ]);

        try {
            app(SystemLogService::class)->logAudit(
                'content.article.updated',
                'Article modifie',
                [
                    'id' => $item->id,
                    'slug' => $item->slug,
                    'status' => $item->status,
                    'content_type' => $item->content_type,
                ],
                'info',
                (string) session('catmin_admin_username', '')
            );
        } catch (\Throwable) {
            // Keep content update resilient if logging fails.
        }

        $this->invalidateCache();

        return $item;
    }

    public function toggleStatus(Article $item): Article
    {
        $next = in_array($item->status, ['published', 'scheduled'], true) ? 'draft' : 'published';
        $item->status = $next;

        if ($next === 'published' && $item->published_at === null) {
            $item->published_at = now();
        }

        $item->save();

        if ($item->status === 'published') {
            CatminEventBus::dispatch(CatminEventBus::ARTICLE_PUBLISHED, [
                'article' => [
                    'id' => $item->id,
                    'title' => $item->title,
                    'slug' => $item->slug,
                    'published_at' => optional($item->published_at)->toIso8601String(),
                ],
            ]);
            Analytics::track('article.published', 'content', 'publish', 'success');
        }

        $this->invalidateCache();

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

    public function bulkPublish(array $ids): int
    {
        $updated = Article::whereIn('id', $ids)
            ->whereNotNull('published_at')
            ->update(['status' => 'published']);

        if ($updated > 0) {
            $this->invalidateCache();
        }

        return $updated;
    }

    public function bulkUnpublish(array $ids): int
    {
        $updated = Article::whereIn('id', $ids)
            ->update(['published_at' => null, 'status' => 'draft']);

        if ($updated > 0) {
            $this->invalidateCache();
        }

        return $updated;
    }

    public function bulkTrash(array $ids): int
    {
        $deleted = Article::whereIn('id', $ids)
            ->whereNull('deleted_at')
            ->delete();

        if ($deleted > 0) {
            $this->invalidateCache();
        }

        return $deleted;
    }

    private function invalidateCache(): void
    {
        QueryCacheService::invalidateModules(['articles', 'dashboard', 'performance']);
    }

    /** @param array<int, mixed> $tagIds */
    private function normalizeTagIds(array $tagIds): array
    {
        $ids = collect($tagIds)
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if ($ids === []) {
            return [];
        }

        return Tag::query()->whereIn('id', $ids)->pluck('id')->map(fn ($id) => (int) $id)->all();
    }
}
