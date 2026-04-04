<?php

namespace Addons\CatEvent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    protected $table = 'events';

    protected $fillable = [
        'title',
        'slug',
        'description',
        'location',
        'address',
        'start_at',
        'end_at',
        'capacity',
        'status',
        'featured_image',
        'organizer_name',
        'organizer_email',
        'is_free',
        'ticket_price',
        'registration_enabled',
        'participation_mode',
        'external_url',
        'allow_waitlist',
        'max_places_per_registration',
        'registration_deadline',
        'published_at',
    ];

    protected $casts = [
        'start_at'              => 'datetime',
        'end_at'                => 'datetime',
        'registration_deadline' => 'datetime',
        'published_at'          => 'datetime',
        'is_free'               => 'boolean',
        'registration_enabled'  => 'boolean',
        'allow_waitlist'        => 'boolean',
        'max_places_per_registration' => 'integer',
        'ticket_price'          => 'decimal:2',
    ];

    public function sessions(): HasMany
    {
        return $this->hasMany(EventSession::class, 'event_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(EventParticipant::class, 'event_id');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(EventTicket::class, 'event_id');
    }

    public function checkins(): HasMany
    {
        return $this->hasMany(EventCheckin::class, 'event_id');
    }

    public function confirmedParticipantsCount(): int
    {
        return $this->participants()->where('status', 'confirmed')->count();
    }

    public function isFull(): bool
    {
        if ($this->capacity === null) {
            return false;
        }

        return $this->confirmedParticipantsCount() >= (int) $this->capacity;
    }

    public function isPublished(): bool
    {
        return in_array($this->status, ['published', 'sold_out', 'finished'], true)
            && $this->published_at !== null
            && $this->published_at->isPast();
    }
}
