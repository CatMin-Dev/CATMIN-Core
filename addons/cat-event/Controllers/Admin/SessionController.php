<?php

namespace Addons\CatEvent\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Addons\CatEvent\Models\Event;
use Addons\CatEvent\Models\EventSession;
use Addons\CatEvent\Services\EventAdminService;

class SessionController extends Controller
{
    public function __construct(private readonly EventAdminService $service)
    {
    }

    public function store(Request $request, Event $event): RedirectResponse
    {
        $validated = $request->validate([
            'title'    => ['required', 'string', 'max:255'],
            'start_at' => ['required', 'date'],
            'end_at'   => ['required', 'date', 'after_or_equal:start_at'],
            'location' => ['nullable', 'string', 'max:255'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'notes'    => ['nullable', 'string'],
        ]);

        $this->service->createSession($event, $validated);

        return back()->with('status', 'Session créée.');
    }

    public function destroy(Event $event, EventSession $session): RedirectResponse
    {
        $this->service->deleteSession($session);

        return back()->with('status', 'Session supprimée.');
    }
}
