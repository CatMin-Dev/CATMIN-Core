<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\Api\V2Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Modules\Articles\Models\Article;
use Modules\Pages\Models\Page;
use Modules\Shop\Models\Product;

class PublicContentController extends Controller
{
    public function settings(Request $request): JsonResponse
    {
        $perPage = $this->resolvePerPage($request, 50);

        $settings = Setting::query()
            ->where('is_public', true)
            ->orderBy('key')
            ->paginate($perPage, ['key', 'value', 'type', 'group'])
            ->appends($request->query())
            ->through(fn (Setting $s) => [
                'key' => $s->key,
                'value' => $s->value,
                'type' => $s->type,
                'group' => $s->group,
            ]);

        return V2Response::success($settings->items(), [
            'resource' => 'settings',
            'scope' => 'public',
            'count' => $settings->count(),
            'pagination' => $this->paginationMeta($settings),
        ]);
    }

    public function pages(Request $request): JsonResponse
    {
        $perPage = $this->resolvePerPage($request);

        $pages = Page::query()
            ->where('status', 'published')
            ->orderByDesc('published_at')
            ->paginate($perPage, ['id', 'title', 'slug', 'published_at', 'updated_at'])
            ->appends($request->query());

        return V2Response::success($pages->items(), [
            'resource' => 'pages',
            'status' => 'published',
            'count' => $pages->count(),
            'pagination' => $this->paginationMeta($pages),
        ]);
    }

    public function articles(Request $request): JsonResponse
    {
        $perPage = $this->resolvePerPage($request);

        $articles = Article::query()
            ->where('status', 'published')
            ->orderByDesc('published_at')
            ->paginate($perPage, [
                'id',
                'title',
                'slug',
                'excerpt',
                'content_type',
                'published_at',
                'updated_at',
            ])
            ->appends($request->query());

        return V2Response::success($articles->items(), [
            'resource' => 'articles',
            'status' => 'published',
            'count' => $articles->count(),
            'pagination' => $this->paginationMeta($articles),
        ]);
    }

    public function shopProducts(Request $request): JsonResponse
    {
        if (!Schema::hasTable('shop_products')) {
            return V2Response::success([], [
                'resource' => 'shop_products',
                'status' => 'unavailable',
                'count' => 0,
            ]);
        }

        $perPage = $this->resolvePerPage($request);

        $products = Product::query()
            ->where('status', 'active')
            ->orderByDesc('id')
            ->paginate($perPage, ['id', 'name', 'slug', 'price', 'updated_at'])
            ->appends($request->query());

        return V2Response::success($products->items(), [
            'resource' => 'shop_products',
            'status' => 'active',
            'count' => $products->count(),
            'pagination' => $this->paginationMeta($products),
        ]);
    }

    private function resolvePerPage(Request $request, int $default = 25): int
    {
        $perPage = (int) $request->query('per_page', $default ?: (int) config('catmin.performance.public_api_default_per_page', 25));
        $maxPerPage = max(1, (int) config('catmin.performance.public_api_max_per_page', 100));

        return max(1, min($maxPerPage, $perPage));
    }

    /**
     * @param \Illuminate\Contracts\Pagination\LengthAwarePaginator<array-key, mixed> $paginator
     * @return array<string, int>
     */
    private function paginationMeta($paginator): array
    {
        return [
            'page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'last_page' => $paginator->lastPage(),
        ];
    }
}
