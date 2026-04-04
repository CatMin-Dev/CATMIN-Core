<?php

declare(strict_types=1);

namespace Addons\CatminBooking\Services;

use Addons\CatminBooking\Models\BookingSlot;

class BookingCalendarService
{
    public function __construct(private readonly AvailabilityEngine $availabilityEngine)
    {
    }

    /**
     * @return array<string,mixed>
     */
    public function range(string $from, string $to, ?int $serviceId = null): array
    {
        $slots = BookingSlot::query()
            ->with(['service:id,name'])
            ->when($serviceId !== null, fn ($q) => $q->where('booking_service_id', $serviceId))
            ->whereBetween('start_at', [$from, $to])
            ->orderBy('start_at')
            ->get();

        return [
            'slots' => $slots->map(function (BookingSlot $slot): array {
                $availability = $this->availabilityEngine->slotAvailability($slot);

                return [
                    'id' => (int) $slot->id,
                    'service_id' => (int) $slot->booking_service_id,
                    'service_name' => (string) ($slot->service->name ?? ''),
                    'start_at' => optional($slot->start_at)->toIso8601String(),
                    'end_at' => optional($slot->end_at)->toIso8601String(),
                    'capacity' => (int) $slot->capacity,
                    'booked_count' => (int) $slot->booked_count,
                    'remaining' => (int) $availability['remaining_capacity'],
                    'is_active' => (bool) $slot->is_active,
                    'status' => (string) $slot->status,
                    'allow_overbooking' => (bool) $slot->allow_overbooking,
                    'blocked_reason' => $slot->blocked_reason,
                    'bookable' => (bool) $availability['bookable'],
                    'blocking_reasons' => $availability['blocking_reasons'],
                ];
            })->values()->all(),
        ];
    }
}
