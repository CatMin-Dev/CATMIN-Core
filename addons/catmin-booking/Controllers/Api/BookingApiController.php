<?php

namespace Addons\CatminBooking\Controllers\Api;

use Addons\CatminBooking\Models\BookingService;
use Addons\CatminBooking\Models\BookingSlot;
use Addons\CatminBooking\Services\BookingAdminService;
use Addons\CatminBooking\Services\BookingCalendarService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingApiController extends Controller
{
    public function __construct(
        private readonly BookingAdminService $service,
        private readonly BookingCalendarService $calendarService,
    )
    {
    }

    public function calendar(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
            'booking_service_id' => ['nullable', 'integer', 'exists:booking_services,id'],
        ]);

        return response()->json([
            'data' => $this->calendarService->range(
                (string) $validated['from'],
                (string) $validated['to'],
                isset($validated['booking_service_id']) ? (int) $validated['booking_service_id'] : null
            ),
        ]);
    }

    public function slotDetails(BookingSlot $bookingSlot): JsonResponse
    {
        $bookingSlot->load(['service:id,name', 'bookings' => fn ($q) => $q->latest('id')->limit(20)]);

        return response()->json([
            'data' => [
                'slot' => [
                    'id' => (int) $bookingSlot->id,
                    'service_name' => (string) ($bookingSlot->service->name ?? ''),
                    'start_at' => optional($bookingSlot->start_at)?->toIso8601String(),
                    'end_at' => optional($bookingSlot->end_at)?->toIso8601String(),
                    'status' => (string) $bookingSlot->status,
                    'capacity' => (int) $bookingSlot->capacity,
                    'booked_count' => (int) $bookingSlot->booked_count,
                    'remaining' => $bookingSlot->remainingCapacity(),
                    'blocked_reason' => $bookingSlot->blocked_reason,
                ],
                'bookings' => $bookingSlot->bookings->map(fn ($booking) => [
                    'id' => (int) $booking->id,
                    'confirmation_code' => (string) $booking->confirmation_code,
                    'customer_name' => (string) $booking->customer_name,
                    'customer_email' => (string) $booking->customer_email,
                    'status' => (string) $booking->status,
                    'created_at' => optional($booking->created_at)?->toIso8601String(),
                ])->values()->all(),
            ],
        ]);
    }

    public function slots(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'booking_service_id' => ['nullable', 'integer', 'exists:booking_services,id'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $slots = $this->service->slots($validated);

        return response()->json([
            'data' => collect($slots->items())->map(fn (BookingSlot $slot) => [
                'id' => (int) $slot->id,
                'booking_service_id' => (int) $slot->booking_service_id,
                'start_at' => optional($slot->start_at)?->toIso8601String(),
                'end_at' => optional($slot->end_at)?->toIso8601String(),
                'capacity' => (int) $slot->capacity,
                'booked_count' => (int) $slot->booked_count,
                'remaining' => $slot->remainingCapacity(),
                'is_active' => (bool) $slot->is_active,
            ])->values()->all(),
            'meta' => [
                'current_page' => $slots->currentPage(),
                'last_page' => $slots->lastPage(),
                'total' => $slots->total(),
            ],
        ]);
    }

    public function services(): JsonResponse
    {
        $services = BookingService::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'duration_minutes', 'price_cents']);

        return response()->json([
            'data' => $services,
        ]);
    }
}
