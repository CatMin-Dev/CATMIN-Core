<?php

namespace Modules\Logger\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Logger\Models\SystemAlert;

class AlertController extends Controller
{
    public function index(Request $request): View
    {
        $severity = trim((string) $request->query('severity', ''));
        $type = trim((string) $request->query('type', ''));
        $state = trim((string) $request->query('state', 'open'));

        $alerts = SystemAlert::query()
            ->when($severity !== '', fn ($query) => $query->where('severity', $severity))
            ->when($type !== '', fn ($query) => $query->where('alert_type', $type))
            ->when($state === 'open', fn ($query) => $query->where('acknowledged', false))
            ->when($state === 'ack', fn ($query) => $query->where('acknowledged', true))
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $types = SystemAlert::query()
            ->select('alert_type')
            ->groupBy('alert_type')
            ->orderBy('alert_type')
            ->pluck('alert_type')
            ->map(fn ($item) => (string) $item)
            ->values()
            ->all();

        return view()->file(base_path('modules/Logger/Views/alerts.blade.php'), [
            'currentPage' => 'logger-alerts',
            'alerts' => $alerts,
            'selectedSeverity' => $severity,
            'selectedType' => $type,
            'selectedState' => $state,
            'types' => $types,
        ]);
    }

    public function acknowledge(Request $request): RedirectResponse
    {
        $ids = collect((array) $request->input('ids', []))
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        if ($ids === []) {
            return back()->with('error', 'Aucune alerte sélectionnée.');
        }

        $username = (string) ($request->session()->get('catmin_admin_username', '') ?: 'admin');

        $updated = SystemAlert::query()
            ->whereIn('id', $ids)
            ->where('acknowledged', false)
            ->update([
                'acknowledged' => true,
                'acknowledged_at' => now(),
                'acknowledged_by' => $username,
            ]);

        return back()->with('success', $updated . ' alerte(s) acquittée(s).');
    }
}
