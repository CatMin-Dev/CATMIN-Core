<?php

namespace Modules\Logger\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Logger\Models\SystemLog;
use Modules\Logger\Models\SystemAlert;
use Modules\Logger\Services\LogMaintenanceService;

class LogController extends Controller
{
    public function __construct(private readonly LogMaintenanceService $maintenance)
    {
    }

    public function index(Request $request): View
    {
        $level = (string) $request->query('level', '');
        $channel = (string) $request->query('channel', '');
        $event = (string) $request->query('event', '');
        $admin = (string) $request->query('admin', '');
        $query = trim((string) $request->query('q', ''));
        $from = (string) $request->query('from', '');
        $to = (string) $request->query('to', '');
        $status = trim((string) $request->query('status', ''));
        $perPageInput = strtolower(trim((string) $request->query('per_page', '50')));
        $perPage = match ($perPageInput) {
            '20' => 20,
            '50' => 50,
            '100' => 100,
            '250' => 250,
            'all' => 1000,
            default => 50,
        };

        $logs = SystemLog::query()
            ->when($level !== '', fn ($query) => $query->where('level', $level))
            ->when($channel !== '', fn ($query) => $query->where('channel', $channel))
            ->when($event !== '', fn ($query) => $query->where('event', $event))
            ->when($admin !== '', fn ($query) => $query->where('admin_username', $admin))
            ->when($status !== '' && is_numeric($status), fn ($query) => $query->where('status_code', (int) $status))
            ->when($query !== '', function ($builder) use ($query): void {
                $builder->where(function ($q) use ($query): void {
                    $q->where('message', 'like', '%' . $query . '%')
                        ->orWhere('event', 'like', '%' . $query . '%')
                        ->orWhere('url', 'like', '%' . $query . '%');
                });
            })
            ->when($from !== '', fn ($query) => $query->where('created_at', '>=', $from . ' 00:00:00'))
            ->when($to !== '', fn ($query) => $query->where('created_at', '<=', $to . ' 23:59:59'))
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        $alertSummary = [
            'unacknowledged' => SystemAlert::query()->where('acknowledged', false)->count(),
            'critical' => SystemAlert::query()->where('acknowledged', false)->where('severity', 'critical')->count(),
        ];

        $recentAlerts = SystemAlert::query()
            ->where('acknowledged', false)
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        $levels = SystemLog::query()
            ->select('level')
            ->whereNotNull('level')
            ->groupBy('level')
            ->orderBy('level')
            ->pluck('level')
            ->map(fn ($item) => (string) $item)
            ->values()
            ->all();

        $channels = SystemLog::query()
            ->select('channel')
            ->whereNotNull('channel')
            ->groupBy('channel')
            ->orderBy('channel')
            ->pluck('channel')
            ->map(fn ($item) => (string) $item)
            ->values()
            ->all();

        $events = SystemLog::query()
            ->select('event')
            ->whereNotNull('event')
            ->groupBy('event')
            ->orderBy('event')
            ->limit(100)
            ->pluck('event')
            ->map(fn ($item) => (string) $item)
            ->values()
            ->all();

        $admins = SystemLog::query()
            ->select('admin_username')
            ->where('admin_username', '!=', '')
            ->groupBy('admin_username')
            ->orderBy('admin_username')
            ->limit(100)
            ->pluck('admin_username')
            ->map(fn ($item) => (string) $item)
            ->values()
            ->all();

        return view()->file(base_path('modules/Logger/Views/index.blade.php'), [
            'currentPage' => 'logger',
            'logs' => $logs,
            'selectedLevel' => $level,
            'selectedChannel' => $channel,
            'selectedEvent' => $event,
            'selectedAdmin' => $admin,
            'searchQuery' => $query,
            'selectedFrom' => $from,
            'selectedTo' => $to,
            'selectedStatus' => $status,
            'selectedPerPage' => $perPageInput,
            'levels' => $levels,
            'channels' => $channels,
            'events' => $events,
            'admins' => $admins,
            'alertSummary' => $alertSummary,
            'recentAlerts' => $recentAlerts,
        ]);
    }

    public function purge(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'level' => ['nullable', 'string', 'max:32'],
            'channel' => ['nullable', 'string', 'max:32'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $deleted = $this->maintenance->purge([
            'level' => (string) ($validated['level'] ?? ''),
            'channel' => (string) ($validated['channel'] ?? ''),
            'from' => (string) ($validated['from'] ?? ''),
            'to' => (string) ($validated['to'] ?? ''),
        ]);

        return redirect()->route('admin.logger.index')->with('success', $deleted . ' log(s) supprimé(s).');
    }
}
