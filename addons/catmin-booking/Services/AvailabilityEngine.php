<?php

declare(strict_types=1);

namespace Addons\CatminBooking\Services;

use Addons\CatminBooking\Models\Booking;
use Addons\CatminBooking\Models\BookingSlot;

class AvailabilityEngine
{
    public function __construct(private readonly BookingPolicyService $policyService)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function slotAvailability(BookingSlot $slot): array
    {
        $remaining = $slot->remainingCapacity();
        $bookable = $this->policyService->slotIsBookable($slot);

        if (!$slot->allow_overbooking && $remaining <= 0) {
            $bookable = false;
        }

        return [
            'slot_id' => (int) $slot->id,
            'bookable' => $bookable,
            'remaining_capacity' => $remaining,
            'capacity' => (int) $slot->capacity,
            'booked_count' => (int) $slot->booked_count,
            'blocking_reasons' => $this->policyService->blockingReasons($slot),
        ];
    }

    public function canConfirm(BookingSlot $slot, string $newStatus = 'confirmed'): bool
    {
        if (!$this->policyService->slotIsBookable($slot)) {
            return false;
        }

        if (!$this->policyService->consumesCapacity($newStatus)) {
            return true;
        }

        if ($slot->allow_overbooking) {
            return true;
        }

        return $slot->remainingCapacity() > 0;
    }

    public function hasDuplicateBooking(BookingSlot $slot, string $email): bool
    {
        return Booking::query()
            ->where('booking_slot_id', $slot->id)
            ->where('customer_email', strtolower(trim($email)))
            ->whereIn('status', ['pending', 'confirmed', 'completed'])
            ->exists();
    }
}
