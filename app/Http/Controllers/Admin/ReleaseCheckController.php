<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\QaFinalGateService;
use Illuminate\Http\View;

final class ReleaseCheckController extends Controller
{
    public function index(): View
    {
        $report = QaFinalGateService::run(
            withAutomatedTests: false,
            strictManual: false
        );

        $status = (string) ($report['status'] ?? 'NOT READY');
        $isReady = $status === 'READY';
        $blockers = (array) ($report['blockers'] ?? []);
        $summary = (array) ($report['summary'] ?? []);
        $sections = (array) ($report['sections'] ?? []);

        return view('admin.pages.release-check', [
            'currentPage' => 'release-check',
            'report' => $report,
            'status' => $status,
            'isReady' => $isReady,
            'blockers' => $blockers,
            'summary' => $summary,
            'sections' => $sections,
            'generatedAt' => (string) ($report['generated_at'] ?? now()->toIso8601String()),
        ]);
    }
}
