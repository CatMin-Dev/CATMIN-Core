<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\Api\V2Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Schema;
use Modules\Articles\Models\Article;
use Modules\Pages\Models\Page;
use Modules\Shop\Models\Product;

class PublicContentController extends Controller
{
    public function settings(): JsonResponse
    {
        $settings = Setting::query()
            ->where('is_public', true)
            ->orderBy('key')
            ->get(['key', 'value', 'type', 'group'])
            ->map(fn (Setting $s) => [
                'key' => $s->key,
                'value' => $s->value,
                'type' => $s->type,
                'group' => $s->group,
            ])
            ->values();

        return V2Response::success($settings, [
            'resource' => 'settings',
            'scope' => 'public',
            'count' => $settings->count(),
        ]);
    }

    public function pages(): JsonResponse
    {
        $pages = Page::query()
            ->where('status', 'published')
            ->orderByDesc('published_at')
            ->limit(100)
            ->get(['id', 'title', 'slug', 'content', 'published_at', 'updated_at']);

        return V2Response::success($pages, [
            'resource' => 'pages',
            'status' => 'published',
            'count' => $pages->count(),
        ]);
    }

    public function articles(): JsonResponse
    {
        $articles = Article::query()
            ->where('status', 'published')
            ->orderByDesc('published_at')
            ->limit(100)
            ->get([
                'id',
                'title',
                'slug',
                'excerpt',
                'content_type',
                'published_at',
                'updated_at',
            ]);

        return V2Response::success($articles, [
            'resource' => 'articles',
            'status' => 'published',
            'count' => $articles->count(),
        ]);
    }

    public function shopProducts(): JsonResponse
    {
        if (!Schema::hasTable('shop_products')) {
            return V2Response::success([], [
                'resource' => 'shop_products',
                'status' => 'unavailable',
                'count' => 0,
            ]);
        }

        $products = Product::query()
            ->where('status', 'active')
            ->orderByDesc('id')
            ->limit(100)
            ->get(['id', 'name', 'slug', 'price', 'description', 'updated_at']);

        return V2Response::success($products, [
            'resource' => 'shop_products',
            'status' => 'active',
            'count' => $products->count(),
        ]);
    }
}
