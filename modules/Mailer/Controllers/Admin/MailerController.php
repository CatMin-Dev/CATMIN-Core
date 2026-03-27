<?php

namespace Modules\Mailer\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Mailer\Models\MailerTemplate;
use Modules\Mailer\Services\MailerAdminService;

class MailerController extends Controller
{
    public function __construct(private readonly MailerAdminService $mailerAdminService)
    {
    }

    public function index(): View
    {
        return view()->file(base_path('modules/Mailer/Views/index.blade.php'), [
            'currentPage' => 'module-mailer',
            'config' => $this->mailerAdminService->getOrCreateConfig(),
            'templates' => $this->mailerAdminService->templateListing(),
            'history' => $this->mailerAdminService->historyListing(),
        ]);
    }

    public function updateConfig(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'driver' => ['required', Rule::in(['smtp', 'mailgun', 'ses', 'log'])],
            'from_email' => ['nullable', 'email', 'max:255'],
            'from_name' => ['nullable', 'string', 'max:255'],
            'reply_to_email' => ['nullable', 'email', 'max:255'],
            'is_enabled' => ['nullable', 'boolean'],
        ]);

        $validated['is_enabled'] = $request->boolean('is_enabled');

        $this->mailerAdminService->updateConfig($validated);

        return redirect()->route('admin.mailer.manage')
            ->with('status', 'Configuration Mailer mise a jour.');
    }

    public function createTemplate(): View
    {
        return view()->file(base_path('modules/Mailer/Views/template-create.blade.php'), [
            'currentPage' => 'module-mailer',
        ]);
    }

    public function storeTemplate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['nullable', 'string', 'max:120'],
            'name' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'body_html' => ['nullable', 'string'],
            'body_text' => ['nullable', 'string'],
            'is_enabled' => ['nullable', 'boolean'],
        ]);

        $validated['is_enabled'] = $request->boolean('is_enabled', true);

        $this->mailerAdminService->createTemplate($validated);

        return redirect()->route('admin.mailer.manage')
            ->with('status', 'Template Mailer cree.');
    }

    public function editTemplate(MailerTemplate $template): View
    {
        return view()->file(base_path('modules/Mailer/Views/template-edit.blade.php'), [
            'currentPage' => 'module-mailer',
            'template' => $template,
        ]);
    }

    public function updateTemplate(Request $request, MailerTemplate $template): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['nullable', 'string', 'max:120'],
            'name' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'body_html' => ['nullable', 'string'],
            'body_text' => ['nullable', 'string'],
            'is_enabled' => ['nullable', 'boolean'],
        ]);

        $validated['is_enabled'] = $request->boolean('is_enabled');

        $this->mailerAdminService->updateTemplate($template, $validated);

        return redirect()->route('admin.mailer.manage')
            ->with('status', 'Template Mailer mis a jour.');
    }
}
