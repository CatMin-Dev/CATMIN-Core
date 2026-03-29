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
        $templates = $this->mailerAdminService->templateListing();

        return view()->file(base_path('modules/Mailer/Views/index.blade.php'), [
            'currentPage' => 'module-mailer',
            'config' => $this->mailerAdminService->getOrCreateConfig(),
            'templates' => $templates,
            'history' => $this->mailerAdminService->historyListing(),
            'defaultTemplateId' => $templates->first()?->id,
        ]);
    }

    public function updateConfig(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'driver' => ['required', Rule::in(['smtp', 'mailgun', 'ses', 'log'])],
            'from_email' => ['nullable', 'email', 'max:255'],
            'from_name' => ['nullable', 'string', 'max:255'],
            'reply_to_email' => ['nullable', 'email', 'max:255'],
            'brand_name' => ['nullable', 'string', 'max:255'],
            'brand_logo_url' => ['nullable', 'url', 'max:500'],
            'brand_primary_color' => ['nullable', 'regex:/^#([A-Fa-f0-9]{6})$/'],
            'brand_footer_text' => ['nullable', 'string', 'max:1000'],
            'sandbox_mode' => ['nullable', 'boolean'],
            'sandbox_recipient' => ['nullable', 'email', 'max:255'],
            'is_enabled' => ['nullable', 'boolean'],
        ]);

        $validated['sandbox_mode'] = $request->boolean('sandbox_mode');
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
        $validated = $this->validateTemplate($request);
        $validated['is_enabled'] = $request->boolean('is_enabled', true);

        $template = $this->mailerAdminService->createTemplate($validated);

        return redirect()->route('admin.mailer.templates.edit', ['template' => $template->id])
            ->with('status', 'Template Mailer cree.');
    }

    public function editTemplate(MailerTemplate $template): View
    {
        return view()->file(base_path('modules/Mailer/Views/template-edit.blade.php'), [
            'currentPage' => 'module-mailer',
            'template' => $template,
            'preview' => $this->mailerAdminService->previewTemplate($template),
        ]);
    }

    public function updateTemplate(Request $request, MailerTemplate $template): RedirectResponse
    {
        $validated = $this->validateTemplate($request);
        $validated['is_enabled'] = $request->boolean('is_enabled');

        $this->mailerAdminService->updateTemplate($template, $validated);

        return redirect()->route('admin.mailer.templates.edit', ['template' => $template->id])
            ->with('status', 'Template Mailer mis a jour.');
    }

    public function testTemplate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'template_id' => ['required', 'integer', 'exists:mailer_templates,id'],
            'recipient' => ['required', 'email', 'max:255'],
            'sample_payload' => ['nullable', 'string'],
            'queue' => ['nullable', 'boolean'],
        ]);

        $template = MailerTemplate::query()->findOrFail((int) $validated['template_id']);
        $history = $this->mailerAdminService->sendTestTemplate(
            $template,
            (string) $validated['recipient'],
            $this->mailerAdminService->normalizePayload($validated['sample_payload'] ?? ''),
            $request->boolean('queue')
        );

        return redirect()->route('admin.mailer.manage')
            ->with('status', 'Email de test lance avec statut ' . $history->status . '.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateTemplate(Request $request): array
    {
        return $request->validate([
            'code' => ['nullable', 'string', 'max:120'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'subject' => ['required', 'string', 'max:255'],
            'body_html' => ['nullable', 'string'],
            'body_text' => ['nullable', 'string'],
            'available_variables' => ['nullable'],
            'sample_payload' => ['nullable'],
            'is_enabled' => ['nullable', 'boolean'],
        ]);
    }
}
