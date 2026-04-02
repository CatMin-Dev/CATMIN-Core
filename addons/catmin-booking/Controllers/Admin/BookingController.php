<?php

namespace Addons\CatminBooking\Controllers\Admin;

use Addons\CatminBooking\Models\Booking;
use Addons\CatminBooking\Models\BookingService;
use Addons\CatminBooking\Models\BookingSlot;
use Addons\CatminBooking\Services\BookingAdminService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function __construct(private readonly BookingAdminService $service)
    {
    }

    public function index(Request $request): View
    {
        return view()->file(base_path('addons/catmin-booking/Views/bookings/index.blade.php'), [
            'currentPage' => 'booking',
            'bookings' => $this->service->bookings($request->only(['status', 'booking_service_id', 'from', 'to'])),
            'statuses' => $this->service->statuses(),
            'servicesList' => BookingService::query()->orderBy('name')->get(['id', 'name']),
            'slots' => BookingSlot::query()->where('is_active', true)->orderBy('start_at')->limit(100)->get(['id', 'booking_service_id', 'start_at', 'end_at']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'booking_slot_id' => ['required', 'integer', 'exists:booking_slots,id'],
            'status' => ['nullable', Rule::in($this->service->statuses())],
            'customer_name' => ['required', 'string', 'max:191'],
            'customer_email' => ['required', 'email', 'max:191'],
            'customer_phone' => ['nullable', 'string', 'max:64'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'internal_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->service->createBooking($validated);

        return redirect()->route('admin.booking.bookings.index')
            ->with('status', 'Réservation créée.');
    }

    public function updateStatus(Request $request, Booking $booking): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in($this->service->statuses())],
            'internal_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->service->updateBookingStatus($booking, (string) $validated['status'], $validated['internal_note'] ?? null);

        return redirect()->route('admin.booking.bookings.index')
            ->with('status', 'Statut réservation mis à jour.');
    }
}
