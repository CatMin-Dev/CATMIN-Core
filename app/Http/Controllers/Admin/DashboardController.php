<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use App\Services\ModuleManager;
use App\Services\SettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use stdClass;

class DashboardController extends Controller
{
    public function index(): View
    {
        $allModules = ModuleManager::all();
        $enabledModules = ModuleManager::enabled();
        $siteName = (string) SettingService::get('site.name', 'CATMIN');
        $siteUrl = (string) SettingService::get('site.url', config('app.url'));

        return view('admin.pages.dashboard', [
            'currentPage' => 'dashboard',
            'welcome' => [
                'site_name' => $siteName,
                'site_url' => $siteUrl,
                'admin_user' => (string) session('catmin_admin_username', config('catmin.admin.username')),
            ],
            'stats' => [
                'users' => User::count(),
                'roles' => Role::count(),
                'settings' => Setting::count(),
                'modules_enabled' => $enabledModules->count(),
                'modules_total' => $allModules->count(),
            ],
            'systemInfo' => [
                'catmin_version' => (string) SettingService::get('system.catmin_version', 'v1-dev'),
                'laravel_version' => app()->version(),
                'php_version' => PHP_VERSION,
                'environment' => app()->environment(),
                'admin_path' => (string) config('catmin.admin.path', 'admin'),
            ],
            'enabledModules' => $enabledModules->take(8)->values(),
            'recentUsers' => User::with('roles')->latest()->limit(5)->get(),
            'contentModules' => collect(['pages', 'blog', 'news', 'media'])
                ->map(fn (string $slug) => ModuleManager::find($slug))
                ->filter()
                ->values(),
        ]);
    }

    public function users(): RedirectResponse
    {
        return redirect()->route('admin.users.manage');
    }

    public function roles(): RedirectResponse
    {
        return redirect()->route('admin.roles.manage');
    }

    public function settings(): RedirectResponse
    {
        return redirect()->route('admin.settings.manage');
    }

    public function modules(): View
    {
        return view('admin.pages.modules.index', [
            'currentPage' => 'modules',
            'modules' => ModuleManager::all(),
        ]);
    }

    public function content(string $module): View|RedirectResponse
    {
        if ($module === 'pages') {
            return redirect()->route('admin.pages.manage');
        }

        $moduleConfig = ModuleManager::find($module);

        abort_if(!$moduleConfig instanceof stdClass, 404);

        return view('admin.pages.content.show', [
            'currentPage' => 'content-' . $module,
            'module' => $moduleConfig,
        ]);
    }
}