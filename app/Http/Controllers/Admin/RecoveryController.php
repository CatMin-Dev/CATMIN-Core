<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\RecoveryEngineService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RecoveryController extends Controller
{
    public function __construct(private readonly RecoveryEngineService $recoveryEngineService)
    {
    }

    public function index(): View
    {
        return view('admin.pages.recovery-center', [
            'currentPage' => 'recovery-center',
            'status' => $this->recoveryEngineService->status(),
            'history' => $this->recoveryEngineService->history(30),
        ]);
    }

    public function run(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'maintenance_mode' => ['nullable', 'in:0,1'],
            'rollback_code' => ['nullable', 'in:0,1'],
            'restore_backup' => ['nullable', 'in:0,1'],
        ]);

        $result = $this->recoveryEngineService->run([
            'maintenance_mode' => ((string) ($validated['maintenance_mode'] ?? '1')) === '1',
            'rollback_code' => ((string) ($validated['rollback_code'] ?? '1')) === '1',
            'restore_backup' => ((string) ($validated['restore_backup'] ?? '1')) === '1',
        ]);

        return redirect()->route('admin.recovery.index')->with(
            ($result['ok'] ?? false) ? 'success' : 'error',
            (string) ($result['message'] ?? 'Operation terminee.')
        );
    }
}
