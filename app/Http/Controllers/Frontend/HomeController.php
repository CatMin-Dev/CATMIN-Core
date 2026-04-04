<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Services\Frontend\FrontendResolverService;
use App\Services\Frontend\PublicContentRenderService;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __construct(
        private readonly FrontendResolverService    $resolver,
        private readonly PublicContentRenderService $renderer,
    ) {}

    public function __invoke(): View
    {
        $context  = $this->resolver->context();
        $homePage = $this->resolver->homePage();
        $seo      = $this->resolver->seo(null, null, ['title' => $context['site_name']]);

        $renderedHome    = $homePage ? $this->renderer->render((string) $homePage->content) : '';
        $latestArticles  = blog_cards(3);

        return view('frontend.home', [
            'siteName'       => $context['site_name'],
            'siteUrl'        => $context['site_url'],
            'seo'            => $seo,
            'primaryMenu'    => $this->resolver->menu('primary'),
            'homePage'       => $homePage,
            'renderedHome'   => $renderedHome,
            'latestArticles' => $latestArticles,
        ]);
    }
}

