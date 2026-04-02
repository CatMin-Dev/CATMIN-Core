<?php

namespace App\Http\Controllers\Api\Internal;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Articles\Models\Article;

class InternalArticlesController extends Controller
{
    /**
     * GET /api/internal/articles/published
     */
    public function publishedArticles(): JsonResponse
    {
        $articles = Article::query()
            ->with(['category:id,name,slug,parent_id', 'tags:id,name,slug'])
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderByDesc('published_at')
            ->limit(100)
            ->get([
                'id',
                'title',
                'slug',
                'excerpt',
                'content_type',
                'article_category_id',
                'published_at',
                'updated_at',
            ]);

        return response()->json([
            'success' => true,
            'data' => $articles,
            'meta' => [
                'count' => $articles->count(),
                'resource' => 'articles',
                'status' => 'published',
            ],
        ]);
    }
}
