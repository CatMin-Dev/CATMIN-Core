<?php

namespace Modules\Settings\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Settings\Services\SettingsAdminService;

class SettingsController extends Controller
{
    public function __construct(private readonly SettingsAdminService $settingsAdminService)
    {
    }

    public function index(): View
    {
        return view()->file(base_path('modules/Settings/Views/index.blade.php'), [
            'currentPage' => 'settings',
            'essentials' => $this->settingsAdminService->essentials(),
            'trackedSettings' => $this->settingsAdminService->recentSettings(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'site_name' => ['required', 'string', 'max:255'],
            'site_url' => ['required', 'string', 'url', 'max:255'],
            'admin_path' => ['required', 'string', 'max:80', 'regex:/^[a-z0-9\/_-]+$/'],
            'admin_theme' => ['required', 'string', 'max:80'],
            'site_frontend_enabled' => ['nullable', 'boolean'],
            'registration_open' => ['nullable', 'boolean'],
        ]);

        $validated['site_frontend_enabled'] = $request->boolean('site_frontend_enabled');
        $validated['registration_open'] = $request->boolean('registration_open');

        $this->settingsAdminService->updateEssentials($validated);

        return redirect()
            ->route('admin.settings.manage')
            ->with('status', 'Parametres enregistres avec succes.');
    }
}
