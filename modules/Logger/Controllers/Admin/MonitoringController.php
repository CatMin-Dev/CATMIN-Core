<?php

namespace Modules\Logger\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\MonitoringService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Logger\Models\MonitoringIncident;

class MonitoringController extends Controller
{
    public function __construct(private readonly MonitoringService $monitoringService)
    {
    }

    public function index(): View
    {
        $report = $this->monitoringService->buildDashboardReport();

        return view()->file(base_path('modules/Logger/Views/monitoring.blade.php'), [
            'currentPage' => 'monitoring',
            'report' => $report,
        ]);
    }

    public function incidents(Request $request): View
    {
        $status = trim((string) $request->query('status', 'open'));
        $domain = trim((string) $request->query('domain', ''));

        $query = MonitoringIncident::query();

        if ($status === 'open') {
            $query->whereIn('status', ['warning', 'degraded', 'critical']);
        } elseif ($status === 'recovered') {
            $query->where('status', 'recovered');
        }

        if ($domain !== '') {
            $query->where('domain', $domain);
        }

        $incidents = $query
            ->orderByRaw("FIELD(status, 'critical', 'degraded', 'warning', 'recovered')")
            ->orderByDesc('last_seen_at')
            ->paginate(30)
            ->withQueryString();

        $domains = MonitoringIncident::query()
            ->select('domain')
            ->groupBy('domain')
            ->orderBy('domain')
            ->pluck('domain')
            ->map(fn ($value) => (string) $value)
            ->values()
            ->all();

        return view()->file(base_path('modules/Logger/Views/monitoring-incidents.blade.php'), [
            'currentPage' => 'monitoring-incidents',
            'incidents' => $incidents,
            'selectedStatus' => $status,
            'selectedDomain' => $domain,
            'domains' => $domains,
        ]);
    }

    public function snapshot(): RedirectResponse
    {
        $result = $this->monitoringService->captureSnapshot();

        return redirect()->route('admin.monitoring.index')
            ->with('status', 'Snapshot monitoring genere. Statut: ' . strtoupper((string) ($result['status'] ?? 'ok')) . '.');
    }
}
