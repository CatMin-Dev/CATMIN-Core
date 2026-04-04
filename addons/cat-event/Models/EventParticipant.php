<?php

namespace Addons\CatEvent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class EventParticipant extends Model
{
    protected $table = 'event_participants';

    protected $fillable = [
        'event_id',
        'event_session_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'seats_count',
        'status',
        'source',
        'idempotency_key',
        'notes',
        'registered_at',
        'confirmed_at',
    ];

    protected $casts = [
        'seats_count' => 'integer',
        'registered_at' => 'datetime',
        'confirmed_at'  => 'datetime',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'event_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(EventSession::class, 'event_session_id');
    }

    public function ticket(): HasOne
    {
        return $this->hasOne(EventTicket::class, 'event_participant_id');
    }

    public function fullName(): string
    {
        return trim($this->first_name . ' ' . ($this->last_name ?? ''));
    }
}
