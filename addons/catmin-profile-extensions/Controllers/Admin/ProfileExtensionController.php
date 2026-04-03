<?php

namespace Addons\CatminProfileExtensions\Controllers\Admin;

use Addons\CatminProfileExtensions\Services\ProfileExtensionService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProfileExtensionController extends Controller
{
    public function __construct(private readonly ProfileExtensionService $profileExtensionService)
    {
    }

    public function update(Request $request): RedirectResponse
    {
        $adminUserId = (int) $request->session()->get('catmin_admin_user_id', 0);

        if ($adminUserId <= 0) {
            return redirect()->route('admin.login');
        }

        $validated = $request->validate([
            'phone' => ['nullable', 'string', 'max:64', 'regex:/^[0-9+()\-\.\s]{6,64}$/'],
            'mobile' => ['nullable', 'string', 'max:64', 'regex:/^[0-9+()\-\.\s]{6,64}$/'],
            'company_name' => ['nullable', 'string', 'max:191'],
            'address_line_1' => ['nullable', 'string', 'max:191'],
            'address_line_2' => ['nullable', 'string', 'max:191'],
            'postal_code' => ['nullable', 'string', 'max:32'],
            'city' => ['nullable', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:120'],
            'country_code' => ['nullable', 'string', 'size:2', 'alpha'],
            'identity_type' => ['nullable', 'string', 'max:64'],
            'identity_number' => ['nullable', 'string', 'max:120'],
            'preferred_contact_method' => ['nullable', 'in:email,phone,mobile,sms,whatsapp'],
            'contact_opt_in' => ['nullable', 'boolean'],
        ]);

        $validated['contact_opt_in'] = $request->boolean('contact_opt_in');

        $this->profileExtensionService->upsertForAdminUser($adminUserId, $validated);

        return redirect()->route('admin.profile.show')->with('status', 'Profil étendu mis à jour.');
    }
}
