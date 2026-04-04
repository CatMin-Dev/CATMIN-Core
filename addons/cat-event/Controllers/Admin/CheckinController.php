<?php

namespace Addons\CatEvent\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Addons\CatEvent\Models\Event;
use Addons\CatEvent\Services\EventCheckinService;

class CheckinController extends Controller
{
    public function __construct(private readonly EventCheckinService $service)
    {
    }

    public function index(Request $request, Event $event): View
    {
        $filters = [
            'status' => (string) $request->query('status', ''),
            'q' => (string) $request->query('q', ''),
        ];

        return view()->file(base_path('addons/cat-event/Views/checkin/index.blade.php'), [
            'currentPage' => 'events',
            'event'       => $event,
            'checkins'    => $this->service->attendanceListing($event, $filters),
            'stats'       => $this->service->attendanceStats($event),
            'filters'     => $filters,
        ]);
    }

    public function store(Request $request, Event $event): RedirectResponse
    {
        $validated = $request->validate([
            'ticket_code' => ['required', 'string', 'max:500'],
            'checkin_method' => ['nullable', 'string', 'max:30'],
            'gate' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $adminUser = auth()->user();
            $this->service->checkinByCode(
                $event,
                $validated['ticket_code'],
                (string) ($validated['checkin_method'] ?? 'manual'),
                $adminUser ? (int) $adminUser->id : null,
                $validated['gate'] ?? null,
                $validated['notes'] ?? null,
            );
        } catch (\RuntimeException $e) {
            return back()->withErrors(['ticket_code' => $e->getMessage()])->withInput();
        }

        return back()->with('status', 'Check-in validé avec succès.');
    }
}
