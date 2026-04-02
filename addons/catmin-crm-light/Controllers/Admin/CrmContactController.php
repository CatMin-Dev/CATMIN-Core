<?php

namespace Addons\CatminCrmLight\Controllers\Admin;

use Addons\CatminCrmLight\Models\CrmCompany;
use Addons\CatminCrmLight\Models\CrmContact;
use Addons\CatminCrmLight\Services\CrmAdminService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CrmContactController extends Controller
{
    public function __construct(private readonly CrmAdminService $service)
    {
    }

    public function index(Request $request): View
    {
        return view()->file(base_path('addons/catmin-crm-light/Views/contacts/index.blade.php'), [
            'currentPage' => 'crm',
            'contacts' => $this->service->contacts($request->only(['q'])),
            'companies' => CrmCompany::query()->orderBy('name')->get(['id', 'name']),
            'statuses' => ['lead', 'active', 'inactive'],
        ]);
    }

    public function show(CrmContact $crmContact): View
    {
        return view()->file(base_path('addons/catmin-crm-light/Views/contacts/show.blade.php'), [
            'currentPage' => 'crm',
            'contact' => $crmContact->load('company', 'crmNotes'),
            'timeline' => $this->service->contactTimeline($crmContact),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'crm_company_id' => ['nullable', 'integer', 'exists:crm_companies,id'],
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['nullable', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:191'],
            'phone' => ['nullable', 'string', 'max:64'],
            'position' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::in(['lead', 'active', 'inactive'])],
            'tags' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ]);

        $this->service->createContact($validated);

        return redirect()->route('admin.crm.contacts.index')->with('status', 'Contact créé.');
    }

    public function update(Request $request, CrmContact $crmContact): RedirectResponse
    {
        $validated = $request->validate([
            'crm_company_id' => ['nullable', 'integer', 'exists:crm_companies,id'],
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['nullable', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:191'],
            'phone' => ['nullable', 'string', 'max:64'],
            'position' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::in(['lead', 'active', 'inactive'])],
            'tags' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ]);

        $this->service->updateContact($crmContact, $validated);

        return redirect()->route('admin.crm.contacts.show', $crmContact->id)->with('status', 'Contact mis à jour.');
    }

    public function destroy(CrmContact $crmContact): RedirectResponse
    {
        $this->service->deleteContact($crmContact);

        return redirect()->route('admin.crm.contacts.index')->with('status', 'Contact supprimé.');
    }

    public function addNote(Request $request, CrmContact $crmContact): RedirectResponse
    {
        $validated = $request->validate([
            'content' => ['required', 'string', 'max:3000'],
            'type' => ['nullable', Rule::in(['note', 'call', 'meeting', 'mail'])],
        ]);

        $this->service->addNote($crmContact, (string) $validated['content'], (string) ($validated['type'] ?? 'note'));

        return redirect()->route('admin.crm.contacts.show', $crmContact->id)->with('status', 'Note ajoutée.');
    }

    public function sendMail(Request $request, CrmContact $crmContact): RedirectResponse
    {
        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:191'],
            'message' => ['required', 'string', 'max:4000'],
        ]);

        $ok = $this->service->sendContactMail($crmContact, (string) $validated['subject'], (string) $validated['message']);

        return redirect()->route('admin.crm.contacts.show', $crmContact->id)
            ->with($ok ? 'status' : 'error', $ok ? 'Email envoyé.' : 'Email non envoyé.');
    }
}
