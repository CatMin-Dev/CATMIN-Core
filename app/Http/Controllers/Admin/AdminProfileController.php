<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Services\AdminProfileService;
use App\Services\AdminSessionService;
use App\Services\AddonManager;
use App\Services\LocaleService;
use App\Services\ProfileExtensionResolverService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminProfileController extends Controller
{
    public function __construct(
        private readonly AdminProfileService $adminProfileService,
        private readonly AdminSessionService $adminSessionService,
        private readonly ProfileExtensionResolverService $profileExtensionResolver,
    ) {
    }

    public function show(Request $request): View|RedirectResponse
    {
        $adminUser = $this->currentAdminUser($request);
        if ($adminUser === null) {
            return redirect()->route('admin.login');
        }

        return view('admin.pages.profile.index', [
            'currentPage' => 'profile',
            'adminUser' => $adminUser,
            'sessions' => $this->adminSessionService->listActiveForAdmin((int) $adminUser->id),
            'currentSessionId' => $request->session()->getId(),
            'canUseMediaPicker' => $this->adminProfileService->mediaTableExists(),
            'profileExtensionEnabled' => AddonManager::enabled()->contains(function ($addon): bool {
                return (string) ($addon->slug ?? '') === 'catmin-profile-extensions';
            }),
            'profileExtension' => $this->profileExtensionResolver->forAdminUser((int) $adminUser->id),
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $adminUser = $this->currentAdminUser($request);
        if ($adminUser === null) {
            return redirect()->route('admin.login');
        }

        $rules = [
            'first_name' => ['nullable', 'string', 'max:120'],
            'last_name' => ['nullable', 'string', 'max:120'],
            'contact_email' => ['nullable', 'email', 'max:191'],
            'phone' => ['nullable', 'string', 'max:64'],
        ];

        if (Schema::hasTable('admin_users') && Schema::hasColumn('admin_users', 'contact_email')) {
            $rules['contact_email'][] = Rule::unique('admin_users', 'contact_email')->ignore((int) $adminUser->id);
        }

        $validated = $request->validate($rules);

        $this->adminProfileService->updateProfile($adminUser, $validated);

        return redirect()->route('admin.profile.show')->with('status', 'Profil mis a jour.');
    }

    public function updateAvatar(Request $request): RedirectResponse
    {
        $adminUser = $this->currentAdminUser($request);
        if ($adminUser === null) {
            return redirect()->route('admin.login');
        }

        $rules = [
            'avatar_media_asset_id' => ['nullable', 'integer', 'min:1'],
        ];

        if ($this->adminProfileService->mediaTableExists()) {
            $rules['avatar_media_asset_id'][] = Rule::exists('media_assets', 'id');
        }

        $validated = $request->validate($rules);

        $avatarId = isset($validated['avatar_media_asset_id'])
            ? (int) $validated['avatar_media_asset_id']
            : null;

        $this->adminProfileService->updateAvatar($adminUser, $avatarId);

        return redirect()->route('admin.profile.show')->with('status', 'Avatar mis a jour.');
    }

    public function changePassword(Request $request): RedirectResponse
    {
        $adminUser = $this->currentAdminUser($request);
        if ($adminUser === null) {
            return redirect()->route('admin.login');
        }

        $validated = $request->validate([
            'current_password' => ['required', 'string', 'max:255'],
            'new_password' => ['required', 'string', 'min:12', 'confirmed'],
        ]);

        $changed = $this->adminProfileService->changePassword(
            $adminUser,
            (string) $validated['current_password'],
            (string) $validated['new_password']
        );

        if (!$changed) {
            return redirect()->route('admin.profile.show')->withErrors([
                'current_password' => 'Mot de passe actuel invalide.',
            ]);
        }

        return redirect()->route('admin.profile.show')->with('status', 'Mot de passe modifie.');
    }

    private function currentAdminUser(Request $request): ?AdminUser
    {
        $adminUserId = (int) $request->session()->get('catmin_admin_user_id', 0);

        return $adminUserId > 0 ? AdminUser::query()->find($adminUserId) : null;
    }

    public function updateLocale(Request $request): RedirectResponse
    {
        $adminUser = $this->currentAdminUser($request);
        if ($adminUser === null) {
            return redirect()->route('admin.login');
        }

        $validated = $request->validate([
            'locale' => ['required', 'string', 'in:' . implode(',', LocaleService::SUPPORTED_LOCALES)],
        ]);

        LocaleService::persistForUser($adminUser, (string) $validated['locale']);
        LocaleService::apply((string) $validated['locale']);

        return redirect()->route('admin.profile.show')->with('status', __('users.locale_saved'));
    }
}
