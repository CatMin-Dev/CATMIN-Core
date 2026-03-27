<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Services\ModuleManager;
use App\Services\SettingService;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $context = frontend_context();

        return view('frontend.home', [
            'siteName' => $context['site_name'],
            'siteUrl' => $context['site_url'],
            'frontendConfig' => config('catmin.frontend'),
            'enabledModules' => ModuleManager::enabled(),
            'siteSettings' => SettingService::group('site'),
            'frontendContext' => $context,
            'homePage' => page_by_slug('home'),
        ]);
    }
}
