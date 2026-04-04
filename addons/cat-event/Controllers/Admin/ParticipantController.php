<?php

namespace Addons\CatEvent\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Addons\CatEvent\Models\Event;
use Addons\CatEvent\Models\EventParticipant;
use Addons\CatEvent\Services\EventAdminService;

class ParticipantController extends Controller
{
    public function __construct(private readonly EventAdminService $service)
    {
    }

    public function index(Event $event): View
    {
        return view()->file(base_path('addons/cat-event/Views/participants/index.blade.php'), [
            'currentPage'  => 'events',
            'event'        => $event->load('sessions'),
            'participants' => $event->participants()->with('session', 'ticket')->orderByDesc('id')->paginate(25),
        ]);
    }

    public function store(Request $request, Event $event): RedirectResponse
    {
        $validated = $request->validate([
            'first_name'       => ['required', 'string', 'max:120'],
            'last_name'        => ['nullable', 'string', 'max:120'],
            'email'            => ['required', 'email', 'max:191'],
            'phone'            => ['nullable', 'string', 'max:50'],
            'event_session_id' => ['nullable', 'integer', 'exists:event_sessions,id'],
            'notes'            => ['nullable', 'string'],
        ]);

        $this->service->registerParticipant($event, $validated);

        return back()->with('status', 'Participant inscrit et billet généré.');
    }

    public function updateStatus(Request $request, Event $event, EventParticipant $participant): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['pending', 'approved', 'confirmed', 'cancelled', 'waitlisted', 'ticketed', 'attended'])],
        ]);

        $this->service->updateParticipantStatus($participant, $validated['status']);

        return back()->with('status', 'Statut mis à jour.');
    }

    public function destroy(Event $event, EventParticipant $participant): RedirectResponse
    {
        $this->service->deleteParticipant($participant);

        return back()->with('status', 'Participant supprimé.');
    }
}
