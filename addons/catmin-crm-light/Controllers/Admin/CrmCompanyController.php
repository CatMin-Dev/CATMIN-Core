<?php

namespace Addons\CatminCrmLight\Controllers\Admin;

use Addons\CatminCrmLight\Models\CrmCompany;
use Addons\CatminCrmLight\Services\CrmAdminService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CrmCompanyController extends Controller
{
    public function __construct(private readonly CrmAdminService $service)
    {
    }

    public function index(Request $request): View
    {
        return view()->file(base_path('addons/catmin-crm-light/Views/companies/index.blade.php'), [
            'currentPage' => 'crm',
            'companies' => $this->service->companies($request->only(['q'])),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'website' => ['nullable', 'url', 'max:255'],
            'industry' => ['nullable', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:191'],
            'phone' => ['nullable', 'string', 'max:64'],
            'address' => ['nullable', 'string', 'max:3000'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ]);

        $this->service->createCompany($validated);

        return redirect()->route('admin.crm.companies.index')->with('status', 'Entreprise créée.');
    }

    public function update(Request $request, CrmCompany $crmCompany): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'website' => ['nullable', 'url', 'max:255'],
            'industry' => ['nullable', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:191'],
            'phone' => ['nullable', 'string', 'max:64'],
            'address' => ['nullable', 'string', 'max:3000'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ]);

        $this->service->updateCompany($crmCompany, $validated);

        return redirect()->route('admin.crm.companies.index')->with('status', 'Entreprise mise à jour.');
    }

    public function destroy(CrmCompany $crmCompany): RedirectResponse
    {
        $this->service->deleteCompany($crmCompany);

        return redirect()->route('admin.crm.companies.index')->with('status', 'Entreprise supprimée.');
    }
}
