<?php

namespace Addons\CatminBooking\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BookingService extends Model
{
    protected $table = 'booking_services';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'duration_minutes',
        'price_cents',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'duration_minutes' => 'integer',
        'price_cents' => 'integer',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    public function slots(): HasMany
    {
        return $this->hasMany(BookingSlot::class, 'booking_service_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'booking_service_id');
    }
}
