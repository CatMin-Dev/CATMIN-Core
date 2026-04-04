<?php

namespace Addons\CatminBooking\Controllers\Admin;

use Addons\CatminBooking\Models\BookingService;
use Addons\CatminBooking\Models\BookingSlot;
use Addons\CatminBooking\Services\BookingAdminService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookingSlotController extends Controller
{
    public function __construct(private readonly BookingAdminService $service)
    {
    }

    public function index(Request $request): View
    {
        return view()->file(base_path('addons/catmin-booking/Views/slots/index.blade.php'), [
            'currentPage' => 'booking',
            'slots' => $this->service->slots($request->only(['booking_service_id', 'from', 'to'])),
            'servicesList' => BookingService::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'booking_service_id' => ['required', 'integer', 'exists:booking_services,id'],
            'start_at' => ['required', 'date'],
            'end_at' => ['required', 'date', 'after:start_at'],
            'capacity' => ['required', 'integer', 'min:1', 'max:300'],
            'status' => ['nullable', 'in:open,closed,blocked'],
            'allow_overbooking' => ['nullable', 'boolean'],
            'blocked_reason' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $this->service->createSlot($validated);

        return redirect()->route('admin.booking.slots.index')
            ->with('status', 'Créneau créé.');
    }

    public function update(Request $request, BookingSlot $bookingSlot): RedirectResponse
    {
        $validated = $request->validate([
            'booking_service_id' => ['required', 'integer', 'exists:booking_services,id'],
            'start_at' => ['required', 'date'],
            'end_at' => ['required', 'date', 'after:start_at'],
            'capacity' => ['required', 'integer', 'min:1', 'max:300'],
            'status' => ['nullable', 'in:open,closed,blocked'],
            'allow_overbooking' => ['nullable', 'boolean'],
            'blocked_reason' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $this->service->updateSlot($bookingSlot, $validated);

        return redirect()->route('admin.booking.slots.index')
            ->with('status', 'Créneau mis à jour.');
    }

    public function destroy(BookingSlot $bookingSlot): RedirectResponse
    {
        $this->service->deleteSlot($bookingSlot);

        return redirect()->route('admin.booking.slots.index')
            ->with('status', 'Créneau supprimé.');
    }
}
