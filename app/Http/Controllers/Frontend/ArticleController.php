<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Services\Frontend\FrontendResolverService;
use App\Services\Frontend\PublicContentRenderService;
use App\Services\ModuleManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Articles\Models\Article;

class ArticleController extends Controller
{
    public function __construct(
        private readonly FrontendResolverService    $resolver,
        private readonly PublicContentRenderService $renderer,
    ) {}

    /**
     * Public article listing — published articles only, paginated.
     */
    public function index(Request $request): View
    {
        abort_unless(
            ModuleManager::isEnabled('articles') && Schema::hasTable('articles'),
            404
        );

        $perPage  = (int) config('catmin_frontend.articles_per_page', 12);
        $articles = Article::query()
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderByDesc('published_at')
            ->paginate($perPage);

        $siteName = $this->resolver->siteName();
        $seo = $this->resolver->seo(null, null, [
            'title'   => 'Articles – ' . $siteName,
            'og_type' => 'website',
        ]);

        return view('frontend.articles.index', [
            'articles'    => $articles,
            'siteName'    => $siteName,
            'seo'         => $seo,
            'primaryMenu' => $this->resolver->menu('primary'),
        ]);
    }

    /**
     * Single published article — resolved by slug.
     */
    public function show(string $slug): View
    {
        abort_unless(
            ModuleManager::isEnabled('articles') && Schema::hasTable('articles'),
            404
        );

        $article = Article::query()
            ->where('slug', $slug)
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->first();

        abort_if($article === null, 404);

        $rendered = $this->renderer->render((string) $article->content);

        $siteName = $this->resolver->siteName();
        $seo = $this->resolver->seo('articles', $article->id, [
            'title'       => ($article->meta_title ?: $article->title) . ' – ' . $siteName,
            'description' => $article->meta_description
                ?: Str::limit(strip_tags((string) $article->content), 160),
            'og_type'     => 'article',
        ]);

        return view('frontend.articles.show', [
            'article'         => $article,
            'renderedContent' => $rendered,
            'readingTime'     => $this->renderer->readingTime((string) $article->content),
            'siteName'        => $siteName,
            'seo'             => $seo,
            'primaryMenu'     => $this->resolver->menu('primary'),
        ]);
    }
}
