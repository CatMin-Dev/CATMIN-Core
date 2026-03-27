<?php

namespace App\Http\Controllers\Api\Internal;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Pages\Models\Page;

class InternalPagesController extends Controller
{
    /**
     * GET /api/internal/pages/published
     */
    public function publishedPages(): JsonResponse
    {
        $pages = Page::query()
            ->where('status', 'published')
            ->orderByDesc('published_at')
            ->limit(100)
            ->get(['id', 'title', 'slug', 'content', 'published_at', 'updated_at']);

        return response()->json([
            'success' => true,
            'data' => $pages,
            'meta' => [
                'count' => $pages->count(),
                'resource' => 'pages',
                'status' => 'published',
            ],
        ]);
    }
}
