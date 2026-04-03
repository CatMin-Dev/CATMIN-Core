<?php

namespace Modules\Notifications\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Notifications\AdminNotificationService;
use App\Services\Notifications\NotificationAggregationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Notifications\Models\AdminNotification;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'type' => trim((string) $request->query('type', '')),
            'source' => trim((string) $request->query('source', '')),
            'read' => trim((string) $request->query('read', '')),
            'critical_only' => (bool) $request->query('critical_only', false),
        ];

        $notifications = AdminNotificationService::listing($filters, 25);
        $stats = AdminNotificationService::dashboardStats();

        $sources = AdminNotification::query()
            ->selectRaw('source')
            ->groupBy('source')
            ->orderBy('source')
            ->pluck('source')
            ->filter()
            ->values()
            ->all();

        return view()->file(base_path('modules/Notifications/Views/index.blade.php'), [
            'currentPage' => 'notifications',
            'notifications' => $notifications,
            'filters' => $filters,
            'stats' => $stats,
            'sources' => $sources,
        ]);
    }

    public function markRead(AdminNotification $notification): RedirectResponse
    {
        AdminNotificationService::markRead($notification);

        return back()->with('success', 'Notification marquée comme lue.');
    }

    public function markAllRead(): RedirectResponse
    {
        $count = AdminNotificationService::markAllRead();

        return back()->with('success', "{$count} notification(s) marquée(s) comme lue(s).");
    }

    public function acknowledge(AdminNotification $notification): RedirectResponse
    {
        AdminNotificationService::acknowledge($notification);

        return back()->with('success', 'Notification acquittée.');
    }

    public function bulk(Request $request): RedirectResponse
    {
        $action = trim((string) $request->input('bulk_action', ''));
        $ids = array_map('intval', (array) ($request->input('ids', [])));

        if ($ids === []) {
            return back()->with('error', 'Aucune notification sélectionnée.');
        }

        if ($action === 'read') {
            $count = AdminNotificationService::bulkRead($ids);
            return back()->with('success', "{$count} notification(s) marquée(s) comme lue(s).");
        }

        if ($action === 'acknowledge') {
            $count = AdminNotificationService::bulkAcknowledge($ids);
            return back()->with('success', "{$count} notification(s) acquittée(s).");
        }

        return back()->with('error', 'Action non reconnue.');
    }

    public function aggregate(): RedirectResponse
    {
        NotificationAggregationService::aggregate();

        return back()->with('success', 'Agrégation des alertes effectuée.');
    }

    public function dropdownData(): \Illuminate\Http\JsonResponse
    {
        $notifications = AdminNotificationService::latestForDropdown(8);
        $unreadCount = AdminNotificationService::unreadCount();

        return response()->json([
            'unread_count' => $unreadCount,
            'notifications' => collect($notifications)->map(fn (AdminNotification $n): array => [
                'id' => $n->id,
                'type' => $n->type,
                'title' => $n->title,
                'message' => $n->message,
                'source' => $n->source,
                'is_read' => $n->is_read,
                'action_url' => $n->action_url,
                'action_label' => $n->action_label,
                'created_at' => $n->created_at?->diffForHumans(),
            ])->all(),
        ]);
    }
}
