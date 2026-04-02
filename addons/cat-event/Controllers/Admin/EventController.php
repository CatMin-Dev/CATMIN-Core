<?php

namespace Addons\CatEvent\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Addons\CatEvent\Models\Event;
use Addons\CatEvent\Services\EventAdminService;

class EventController extends Controller
{
    public function __construct(private readonly EventAdminService $service)
    {
    }

    public function index(): View
    {
        return view()->file(base_path('addons/cat-event/Views/index.blade.php'), [
            'currentPage' => 'events',
            'events'      => $this->service->listing(request()->only(['status', 'location', 'date_from', 'date_to'])),
            'statuses'    => $this->service->statuses(),
        ]);
    }

    public function create(): View
    {
        return view()->file(base_path('addons/cat-event/Views/create.blade.php'), [
            'currentPage' => 'events',
            'statuses'    => $this->service->statuses(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateEvent($request);

        $this->service->create($validated);

        return redirect()->route('admin.events.index')
            ->with('status', 'Événement créé avec succès.');
    }

    public function edit(Event $event): View
    {
        return view()->file(base_path('addons/cat-event/Views/edit.blade.php'), [
            'currentPage' => 'events',
            'event'       => $event->load('sessions'),
            'statuses'    => $this->service->statuses(),
        ]);
    }

    public function update(Request $request, Event $event): RedirectResponse
    {
        $validated = $this->validateEvent($request, $event->id);

        $this->service->update($event, $validated);

        return redirect()->route('admin.events.edit', $event->id)
            ->with('status', 'Événement mis à jour.');
    }

    public function destroy(Event $event): RedirectResponse
    {
        $this->service->delete($event);

        return redirect()->route('admin.events.index')
            ->with('status', 'Événement supprimé.');
    }

    public function toggleStatus(Event $event): RedirectResponse
    {
        $this->service->toggleStatus($event);

        return back()->with('status', 'Statut modifié.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateEvent(Request $request, ?int $excludeId = null): array
    {
        return $request->validate([
            'title'                 => ['required', 'string', 'max:255'],
            'slug'                  => ['nullable', 'string', 'max:255'],
            'description'           => ['nullable', 'string'],
            'location'              => ['nullable', 'string', 'max:255'],
            'address'               => ['nullable', 'string', 'max:1000'],
            'start_at'              => ['required', 'date'],
            'end_at'                => ['required', 'date', 'after_or_equal:start_at'],
            'capacity'              => ['nullable', 'integer', 'min:1'],
            'status'                => ['required', Rule::in($this->service->statuses())],
            'featured_image'        => ['nullable', 'string', 'max:500'],
            'organizer_name'        => ['nullable', 'string', 'max:191'],
            'organizer_email'       => ['nullable', 'email', 'max:191'],
            'is_free'               => ['nullable', 'boolean'],
            'ticket_price'          => ['nullable', 'numeric', 'min:0'],
            'registration_enabled'  => ['nullable', 'boolean'],
            'registration_deadline' => ['nullable', 'date'],
        ]);
    }
}
