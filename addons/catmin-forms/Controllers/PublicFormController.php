<?php

namespace Addons\CatminForms\Controllers;

use Addons\CatminForms\Models\FormDefinition;
use Addons\CatminForms\Services\FormSubmissionService;
use App\Http\Controllers\Controller;
use App\Services\Frontend\FrontendResolverService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicFormController extends Controller
{
    public function __construct(
        private readonly FormSubmissionService $submissionService,
        private readonly FrontendResolverService $frontendResolver,
    ) {
    }

    public function show(string $slug): View
    {
        $form = FormDefinition::query()
            ->where('slug', $slug)
            ->where('status', 'active')
            ->with('fields')
            ->firstOrFail();

        $siteName = $this->frontendResolver->siteName();
        $seo = $this->frontendResolver->seo(null, null, [
            'title' => $form->name . ' - ' . $siteName,
            'og_type' => 'website',
        ]);

        return view()->file(base_path('addons/catmin-forms/Views/public/show.blade.php'), [
            'form' => $form,
            'siteName' => $siteName,
            'seo' => $seo,
            'primaryMenu' => $this->frontendResolver->menu('primary'),
        ]);
    }

    public function submit(Request $request, string $slug): RedirectResponse
    {
        $form = FormDefinition::query()
            ->where('slug', $slug)
            ->where('status', 'active')
            ->with('fields')
            ->firstOrFail();

        $honeypotField = (string) config('catmin_forms.honeypot_field', 'website_url');
        if (trim((string) $request->input($honeypotField, '')) !== '') {
            return back()->with('success', 'Merci, votre message a bien ete envoye.');
        }

        $rules = [];
        foreach ($form->fields as $field) {
            $fieldRules = [];
            $fieldRules[] = $field->is_required ? 'required' : 'nullable';

            if ($field->validation_rules !== null && trim((string) $field->validation_rules) !== '') {
                foreach (explode('|', (string) $field->validation_rules) as $rule) {
                    $trimmed = trim($rule);
                    if ($trimmed !== '') {
                        $fieldRules[] = $trimmed;
                    }
                }
            }

            $rules[$field->key] = $fieldRules;
        }

        $validated = $request->validate($rules);
        $this->submissionService->submit($form, $validated);

        return back()->with('success', 'Votre demande a bien ete transmise.');
    }
}
