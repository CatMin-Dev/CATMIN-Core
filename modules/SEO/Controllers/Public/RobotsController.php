<?php

namespace Modules\SEO\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Modules\SEO\Services\RobotsService;

class RobotsController extends Controller
{
    public function __invoke(RobotsService $service): Response
    {
        return response($service->getContent(), 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }
}
