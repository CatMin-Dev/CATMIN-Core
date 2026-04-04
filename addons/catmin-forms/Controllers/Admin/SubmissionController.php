<?php

namespace Addons\CatminForms\Controllers\Admin;

use Addons\CatminForms\Models\FormDefinition;
use Addons\CatminForms\Models\FormSubmission;
use Addons\CatminForms\Services\FormSubmissionService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubmissionController extends Controller
{
    public function __construct(private readonly FormSubmissionService $service)
    {
    }

    public function index(Request $request): View
    {
        return view()->file(base_path('addons/catmin-forms/Views/submissions/index.blade.php'), [
            'currentPage' => 'forms',
            'submissions' => $this->service->listing($request->only(['status', 'form_definition_id'])),
            'forms' => FormDefinition::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function show(FormSubmission $formSubmission): View
    {
        return view()->file(base_path('addons/catmin-forms/Views/submissions/show.blade.php'), [
            'currentPage' => 'forms',
            'submission' => $formSubmission->load('form'),
        ]);
    }

    public function markProcessed(FormSubmission $formSubmission): RedirectResponse
    {
        $this->service->markProcessed($formSubmission);

        return back()->with('status', 'Soumission marquée comme traitée.');
    }
}
