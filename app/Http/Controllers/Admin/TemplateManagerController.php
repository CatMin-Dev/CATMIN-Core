<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\TemplateInstallerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TemplateManagerController extends Controller
{
    public function __construct(private readonly TemplateInstallerService $templateInstallerService)
    {
    }

    public function index(): View
    {
        $list = $this->templateInstallerService->listTemplates();

        return view('admin.pages.templates.index', [
            'currentPage' => 'settings',
            'templates' => (array) ($list['templates'] ?? []),
            'latestReport' => $this->templateInstallerService->latestReport(),
        ]);
    }

    public function install(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'slug' => ['required', 'string', 'max:120', 'regex:/^[a-z0-9\-]+$/'],
            'overwrite' => ['nullable', 'boolean'],
        ]);

        $result = $this->templateInstallerService->installFromSlug((string) $validated['slug'], [
            'overwrite' => $request->boolean('overwrite'),
            'source' => 'admin_settings',
        ]);

        if (($result['ok'] ?? false) !== true) {
            return redirect()
                ->route('admin.templates.index')
                ->with('error', (string) ($result['message'] ?? 'Installation template echouee.'))
                ->withErrors((array) ($result['errors'] ?? []));
        }

        return redirect()->route('admin.templates.index')->with('status', 'Template installe avec succes.');
    }

    public function export(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'slug' => ['required', 'string', 'max:120', 'regex:/^[a-z0-9\-]+$/'],
            'name' => ['nullable', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:4000'],
        ]);

        $slug = strtolower(trim((string) $validated['slug']));
        $path = base_path('templates/' . $slug . '.template.json');

        $result = $this->templateInstallerService->exportToFile($slug, $path, [
            'name' => (string) ($validated['name'] ?? ''),
            'description' => (string) ($validated['description'] ?? ''),
        ]);

        if (($result['ok'] ?? false) !== true) {
            return redirect()
                ->route('admin.templates.index')
                ->with('error', (string) ($result['message'] ?? 'Export template echoue.'))
                ->withErrors((array) ($result['errors'] ?? []));
        }

        return redirect()->route('admin.templates.index')->with('status', 'Template exporte: ' . $path);
    }
}
