<?php

namespace Addons\CatminImportExport\Controllers\Admin;

use Addons\CatminImportExport\Services\ImportExportAdminService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ImportExportController extends Controller
{
    public function __construct(private readonly ImportExportAdminService $service)
    {
    }

    public function index(): View
    {
        return view()->file(base_path('addons/catmin-import-export/Views/admin/index.blade.php'), [
            'currentPage' => 'import-export',
            'modules' => $this->service->moduleOptions(),
            'formats' => ['json', 'csv'],
            'logs' => $this->service->recentLogs(),
        ]);
    }

    public function export(Request $request): Response
    {
        $validated = $request->validate([
            'module' => ['required', 'string', Rule::in(array_keys($this->service->moduleOptions()))],
            'format' => ['required', 'string', Rule::in(['json', 'csv'])],
        ]);

        $result = $this->service->export($validated['module'], $validated['format']);

        return response($result['content'], 200, [
            'Content-Type' => $result['content_type'],
            'Content-Disposition' => 'attachment; filename="' . $result['filename'] . '"',
        ]);
    }

    public function import(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'module' => ['required', 'string', Rule::in(array_keys($this->service->moduleOptions()))],
            'format' => ['required', 'string', Rule::in(['json', 'csv'])],
            'payload' => ['nullable', 'string'],
            'file' => ['nullable', 'file', 'max:5120'],
            'dry_run' => ['nullable', 'boolean'],
            'overwrite' => ['nullable', 'boolean'],
        ]);

        $payload = (string) ($validated['payload'] ?? '');

        if ($request->hasFile('file')) {
            $payload = (string) $request->file('file')->get();
        }

        if (trim($payload) === '') {
            return redirect()->route('admin.import_export.index')
                ->withErrors(['payload' => 'Fichier ou contenu requis pour importer.'])
                ->withInput();
        }

        $result = $this->service->import(
            $validated['module'],
            $validated['format'],
            $payload,
            (bool) ($validated['dry_run'] ?? false),
            (bool) ($validated['overwrite'] ?? false),
        );

        return redirect()->route('admin.import_export.index')
            ->with('status', $result['message'])
            ->with('import_result', $result);
    }
}