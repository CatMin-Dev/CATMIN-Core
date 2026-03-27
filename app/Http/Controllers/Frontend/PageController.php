<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Services\SettingService;
use Illuminate\View\View;

class PageController extends Controller
{
    public function __invoke(string $slug): View
    {
        $page = page_by_slug($slug, true);
        $renderedContent = inject_blocks((string) ($page?->content ?? ''));

        abort_if($page === null, 404);

        return view('frontend.page', [
            'page' => $page,
            'renderedContent' => $renderedContent,
            'siteName' => SettingService::get('site.name', 'CATMIN'),
            'siteUrl' => SettingService::get('site.url', config('app.url')),
        ]);
    }
}
