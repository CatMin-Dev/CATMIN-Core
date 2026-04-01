<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AutoUpdateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UpdateController extends Controller
{
    public function __construct(private readonly AutoUpdateService $autoUpdateService)
    {
    }

    public function index(): View
    {
        return view('admin.pages.system-update', [
            'currentPage' => 'system-update',
            'status' => $this->autoUpdateService->status(),
            'activeUpdate' => $this->autoUpdateService->activeUpdate(),
            'history' => $this->autoUpdateService->history(30),
        ]);
    }

    public function download(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'package_url' => ['required', 'url'],
            'package_sha256' => ['required', 'regex:/^[a-fA-F0-9]{64}$/'],
        ]);

        $result = $this->autoUpdateService->downloadPackage(
            (string) $validated['package_url'],
            (string) $validated['package_sha256']
        );

        return redirect()->route('admin.system-update.index')->with(
            ($result['ok'] ?? false) ? 'success' : 'error',
            (string) ($result['message'] ?? 'Operation terminee.')
        );
    }

    public function apply(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'with_core_migrate' => ['nullable', 'in:0,1'],
        ]);

        $result = $this->autoUpdateService->applyDownloadedUpdate(
            ((string) ($validated['with_core_migrate'] ?? '1')) === '1'
        );

        return redirect()->route('admin.system-update.index')->with(
            ($result['ok'] ?? false) ? 'success' : 'error',
            (string) ($result['message'] ?? 'Operation terminee.')
        );
    }

    public function rollback(): RedirectResponse
    {
        $result = $this->autoUpdateService->rollbackLast();

        return redirect()->route('admin.system-update.index')->with(
            ($result['ok'] ?? false) ? 'success' : 'error',
            (string) ($result['message'] ?? 'Operation terminee.')
        );
    }
}
