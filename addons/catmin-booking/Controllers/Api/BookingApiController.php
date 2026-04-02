<?php

namespace Addons\CatminBooking\Controllers\Api;

use Addons\CatminBooking\Models\BookingService;
use Addons\CatminBooking\Models\BookingSlot;
use Addons\CatminBooking\Services\BookingAdminService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingApiController extends Controller
{
    public function __construct(private readonly BookingAdminService $service)
    {
    }

    public function calendar(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
        ]);

        return response()->json([
            'data' => $this->service->calendarData((string) $validated['from'], (string) $validated['to']),
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
