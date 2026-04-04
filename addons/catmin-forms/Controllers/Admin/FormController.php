<?php

namespace Addons\CatminForms\Controllers\Admin;

use Addons\CatminForms\Models\FormDefinition;
use Addons\CatminForms\Models\FormField;
use Addons\CatminForms\Services\FormDefinitionService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FormController extends Controller
{
    public function __construct(private readonly FormDefinitionService $service)
    {
    }

    public function index(Request $request): View
    {
        return view()->file(base_path('addons/catmin-forms/Views/forms/index.blade.php'), [
            'currentPage' => 'forms',
            'forms' => $this->service->listing($request->only(['q'])),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'slug' => ['nullable', 'string', 'max:191'],
            'type' => ['required', Rule::in(['contact', 'lead', 'event_request', 'booking_request', 'custom'])],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'mapping' => ['required', Rule::in(['none', 'crm_lead', 'event_preregistration', 'booking_request'])],
            'target_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $this->service->create($validated);

        return redirect()->route('admin.forms.index')->with('status', 'Formulaire créé.');
    }

    public function edit(FormDefinition $formDefinition): View
    {
        return view()->file(base_path('addons/catmin-forms/Views/forms/edit.blade.php'), [
            'currentPage' => 'forms',
            'formItem' => $formDefinition->load('fields'),
        ]);
    }

    public function update(Request $request, FormDefinition $formDefinition): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'type' => ['required', Rule::in(['contact', 'lead', 'event_request', 'booking_request', 'custom'])],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'mapping' => ['required', Rule::in(['none', 'crm_lead', 'event_preregistration', 'booking_request'])],
            'target_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $this->service->update($formDefinition, $validated);

        return redirect()->route('admin.forms.edit', $formDefinition->id)->with('status', 'Formulaire mis à jour.');
    }

    public function destroy(FormDefinition $formDefinition): RedirectResponse
    {
        $this->service->delete($formDefinition);

        return redirect()->route('admin.forms.index')->with('status', 'Formulaire supprimé.');
    }

    public function storeField(Request $request, FormDefinition $formDefinition): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(['text', 'email', 'phone', 'textarea', 'select', 'number'])],
            'label' => ['required', 'string', 'max:191'],
            'key' => ['required', 'string', 'max:120', 'regex:/^[a-z0-9_]+$/'],
            'is_required' => ['nullable', 'boolean'],
            'options' => ['nullable', 'string', 'max:1000'],
            'validation_rules' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:1000'],
        ]);

        $this->service->addField($formDefinition, $validated);

        return redirect()->route('admin.forms.edit', $formDefinition->id)->with('status', 'Champ ajouté.');
    }

    public function destroyField(FormDefinition $formDefinition, FormField $formField): RedirectResponse
    {
        $this->service->removeField($formField);

        return redirect()->route('admin.forms.edit', $formDefinition->id)->with('status', 'Champ supprimé.');
    }
}
