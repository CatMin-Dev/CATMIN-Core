<?php

namespace Addons\CatminBooking\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends Model
{
    protected $table = 'bookings';

    protected $fillable = [
        'booking_service_id',
        'booking_slot_id',
        'status',
        'customer_name',
        'customer_email',
        'customer_phone',
        'notes',
        'internal_note',
        'confirmation_code',
        'confirmed_at',
        'cancelled_at',
    ];

    protected $casts = [
        'confirmed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(BookingService::class, 'booking_service_id');
    }

    public function slot(): BelongsTo
    {
        return $this->belongsTo(BookingSlot::class, 'booking_slot_id');
    }
}
