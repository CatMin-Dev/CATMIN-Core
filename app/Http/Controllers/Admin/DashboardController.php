<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use App\Services\ModuleManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use stdClass;

class DashboardController extends Controller
{
    public function index(): View
    {
        $allModules = ModuleManager::all();

        return view('admin.pages.dashboard', [
            'currentPage' => 'dashboard',
            'stats' => [
                'users' => User::count(),
                'roles' => Role::count(),
                'settings' => Setting::count(),
                'modules_enabled' => ModuleManager::enabled()->count(),
                'modules_total' => $allModules->count(),
            ],
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

    public function settings(): View
    {
        return view('admin.pages.settings.index', [
            'currentPage' => 'settings',
            'settings' => Setting::query()->orderBy('group')->orderBy('key')->get(),
        ]);
    }

    public function modules(): View
    {
        return view('admin.pages.modules.index', [
            'currentPage' => 'modules',
            'modules' => ModuleManager::all(),
        ]);
    }

    public function content(string $module): View
    {
        $moduleConfig = ModuleManager::find($module);

        abort_if(!$moduleConfig instanceof stdClass, 404);

        return view('admin.pages.content.show', [
            'currentPage' => 'content-' . $module,
            'module' => $moduleConfig,
        ]);
    }
}