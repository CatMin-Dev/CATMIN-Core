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
        return view('frontend.home', [
            'siteName' => SettingService::get('site.name', 'CATMIN'),
            'siteUrl' => SettingService::get('site.url', config('app.url')),
            'frontendConfig' => config('catmin.frontend'),
            'enabledModules' => ModuleManager::enabled(),
            'siteSettings' => SettingService::group('site'),
        ]);
    }
}
