<?php

namespace Addons\CatEvent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class EventTicket extends Model
{
    protected $table = 'event_tickets';

    protected $fillable = [
        'event_id',
        'event_participant_id',
        'participant_id',
        'source',
        'ticket_number',
        'code',
        'token',
        'qr_code',
        'qr_payload',
        'status',
        'checkin_at',
        'issued_at',
        'used_at',
    ];

    protected $casts = [
        'checkin_at' => 'datetime',
        'issued_at'  => 'datetime',
        'used_at'    => 'datetime',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'event_id');
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(EventParticipant::class, 'event_participant_id');
    }

    public function checkin(): HasOne
    {
        return $this->hasOne(EventCheckin::class, 'event_ticket_id');
    }

    public function isUsed(): bool
    {
        return $this->status === 'used';
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['active', 'issued'], true);
    }

    public function publicCode(): string
    {
        return (string) ($this->code ?: $this->ticket_number);
    }
}
