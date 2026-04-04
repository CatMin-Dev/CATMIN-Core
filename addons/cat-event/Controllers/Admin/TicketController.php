<?php

namespace Addons\CatEvent\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Addons\CatEvent\Models\Event;
use Addons\CatEvent\Models\EventTicket;
use Addons\CatEvent\Services\EventAdminService;
use Addons\CatEvent\Services\EventTicketService;

class TicketController extends Controller
{
    public function __construct(
        private readonly EventAdminService $service,
        private readonly EventTicketService $ticketService,
    )
    {
    }

    public function index(Event $event): View
    {
        return view()->file(base_path('addons/cat-event/Views/tickets/index.blade.php'), [
            'currentPage' => 'events',
            'event'       => $event,
            'tickets'     => $event->tickets()->with('participant')->orderByDesc('id')->paginate(25),
        ]);
    }

    public function cancel(Event $event, EventTicket $ticket): RedirectResponse
    {
        $this->service->cancelTicket($ticket);

        return back()->with('status', 'Billet annulé.');
    }

    public function regenerate(Event $event, EventTicket $ticket): RedirectResponse
    {
        $this->ticketService->regenerate($ticket);

        return back()->with('status', 'Billet régénéré (token/QR rafraîchis).');
    }
}
