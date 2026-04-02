<?php

namespace Addons\CatminEventShopBridge\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Addons\CatminEventShopBridge\Services\EventShopBridgeService;

class TicketTypeController extends Controller
{
    public function __construct(private readonly EventShopBridgeService $bridgeService)
    {
    }

    public function index(): View
    {
        return view()->file(base_path('addons/catmin-event-shop-bridge/Views/ticket-types/index.blade.php'), [
            'currentPage' => 'event-shop-bridge',
            'ticketTypes' => $this->bridgeService->ticketTypes(),
            'events' => $this->bridgeService->events(),
            'recentLinks' => $this->bridgeService->recentOrderLinks(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'event_id' => ['required', 'integer', 'exists:events,id'],
            'name' => ['required', 'string', 'max:191'],
            'price' => ['required', 'numeric', 'min:0'],
            'allocation' => ['nullable', 'integer', 'min:1'],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'auto_cancel_on_order_cancel' => ['nullable', 'boolean'],
        ]);

        $validated['auto_cancel_on_order_cancel'] = $request->boolean('auto_cancel_on_order_cancel', true);

        $this->bridgeService->createTicketType($validated);

        return redirect()->route('admin.event-shop-bridge.ticket-types.index')
            ->with('status', 'Type de billet bridge cree.');
    }
}