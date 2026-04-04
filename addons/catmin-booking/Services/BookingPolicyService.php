<?php

declare(strict_types=1);

namespace Addons\CatminBooking\Services;

use Addons\CatminBooking\Models\BookingSlot;

class BookingPolicyService
{
    /**
     * @return array<int, string>
     */
    public function allowedStatuses(): array
    {
        return ['pending', 'confirmed', 'cancelled', 'completed', 'no_show'];
    }

    public function slotIsBookable(BookingSlot $slot): bool
    {
        if (!$slot->is_active) {
            return false;
        }

        if (in_array((string) $slot->status, ['closed', 'blocked'], true)) {
            return false;
        }

        if ($slot->start_at === null || $slot->end_at === null) {
            return false;
        }

        if ($slot->end_at->lessThanOrEqualTo(now())) {
            return false;
        }

        return true;
    }

    /**
     * @return array<int, string>
     */
    public function blockingReasons(BookingSlot $slot): array
    {
        $reasons = [];

        if (!$slot->is_active) {
            $reasons[] = 'slot_inactive';
        }

        if ((string) $slot->status === 'closed') {
            $reasons[] = 'slot_closed';
        }

        if ((string) $slot->status === 'blocked') {
            $reasons[] = 'slot_blocked';
        }

        if ($slot->end_at !== null && $slot->end_at->lessThanOrEqualTo(now())) {
            $reasons[] = 'slot_past';
        }

        return $reasons;
    }

    public function consumesCapacity(string $bookingStatus): bool
    {
        return in_array($bookingStatus, ['pending', 'confirmed', 'completed', 'no_show'], true);
    }
}
