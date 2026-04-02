<?php

namespace Addons\CatEvent\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Addons\CatEvent\Models\Event;
use Addons\CatEvent\Models\EventTicket;
use Addons\CatEvent\Services\EventAdminService;

class CheckinController extends Controller
{
    public function __construct(private readonly EventAdminService $service)
    {
    }

    public function index(Event $event): View
    {
        return view()->file(base_path('addons/cat-event/Views/checkin/index.blade.php'), [
            'currentPage' => 'events',
            'event'       => $event,
            'checkins'    => $event->checkins()->with('participant', 'ticket')->orderByDesc('checkin_at')->paginate(50),
            'stats'       => [
                'total_tickets'     => $event->tickets()->where('status', '!=', 'cancelled')->count(),
                'checkins_done'     => $event->checkins()->count(),
                'remaining'         => max(0, $event->tickets()->where('status', 'active')->count()),
            ],
        ]);
    }

    public function store(Request $request, Event $event): RedirectResponse
    {
        $validated = $request->validate([
            'ticket_number' => ['required', 'string'],
        ]);

        $ticket = EventTicket::query()
            ->where('event_id', $event->id)
            ->where('ticket_number', $validated['ticket_number'])
            ->first();

        if ($ticket === null) {
            return back()->withErrors(['ticket_number' => 'Billet introuvable pour cet événement.']);
        }

        try {
            $adminUser = auth()->user();
            $this->service->checkin($ticket, 'manual', $adminUser ? (int) $adminUser->id : null);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['ticket_number' => $e->getMessage()]);
        }

        return back()->with('status', 'Check-in effectué avec succès.');
    }
}
