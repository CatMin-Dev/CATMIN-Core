<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Services\Frontend\FrontendResolverService;
use App\Services\Frontend\PublicContentRenderService;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PageController extends Controller
{
    public function __construct(
        private readonly FrontendResolverService    $resolver,
        private readonly PublicContentRenderService $renderer,
    ) {}

    public function __invoke(string $slug): View
    {
        $page = page_by_slug($slug, true);

        abort_if($page === null, 404);

        $renderedContent = $this->renderer->render((string) $page->content);
        $seo = $this->resolver->seo('pages', $page->id, [
            'title'       => $page->meta_title ?: $page->title . ' – ' . $this->resolver->siteName(),
            'description' => $page->meta_description ?: Str::limit(strip_tags((string) $page->content), 160),
            'og_type'     => 'article',
        ]);

        return view('frontend.page', [
            'page'           => $page,
            'renderedContent' => $renderedContent,
            'siteName'       => $this->resolver->siteName(),
            'siteUrl'        => $this->resolver->siteUrl(),
            'seo'            => $seo,
            'primaryMenu'    => $this->resolver->menu('primary'),
        ]);
    }
}

