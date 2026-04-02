<?php

namespace Modules\SEO\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Modules\SEO\Services\SitemapService;

class SitemapController extends Controller
{
    public function __invoke(SitemapService $service): Response
    {
        return response($service->getXml(), 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }
}
