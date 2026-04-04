<?php

namespace Addons\CatminBooking\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BookingSlot extends Model
{
    protected $table = 'booking_slots';

    protected $fillable = [
        'booking_service_id',
        'start_at',
        'end_at',
        'capacity',
        'booked_count',
        'status',
        'allow_overbooking',
        'blocked_reason',
        'is_active',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'capacity' => 'integer',
        'booked_count' => 'integer',
        'allow_overbooking' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(BookingService::class, 'booking_service_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'booking_slot_id');
    }

    public function remainingCapacity(): int
    {
        return max(0, (int) $this->capacity - (int) $this->booked_count);
    }
}
