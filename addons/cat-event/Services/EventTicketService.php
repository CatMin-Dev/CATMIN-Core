<?php

declare(strict_types=1);

namespace Addons\CatEvent\Services;

use App\Services\CatminEventBus;
use Addons\CatEvent\Models\Event;
use Addons\CatEvent\Models\EventParticipant;
use Addons\CatEvent\Models\EventTicket;
use Illuminate\Support\Str;

class EventTicketService
{
    public function __construct(private readonly EventQrCodeService $qrCodeService)
    {
    }

    public function issue(Event $event, EventParticipant $participant, string $source = 'manual'): EventTicket
    {
        $code = $this->nextUniqueCode($event);

        /** @var EventTicket $ticket */
        $ticket = EventTicket::query()->create([
            'event_id' => $event->id,
            'event_participant_id' => $participant->id,
            'participant_id' => $participant->id,
            'source' => $source,
            'ticket_number' => $code,
            'code' => $code,
            'token' => Str::random(48),
            'status' => 'issued',
            'issued_at' => now(),
        ]);

        $payload = $this->qrCodeService->payloadJson($ticket);

        $ticket->update([
            'qr_payload' => $payload,
            'qr_code' => $this->qrCodeService->svgDataUri($payload),
        ]);

        CatminEventBus::dispatch('event.ticket.issued', [
            'event_id' => $event->id,
            'participant_id' => $participant->id,
            'ticket_id' => $ticket->id,
            'source' => $source,
        ]);

        return $ticket->fresh() ?? $ticket;
    }

    public function regenerate(EventTicket $ticket): EventTicket
    {
        $ticket->update([
            'token' => Str::random(48),
            'status' => $ticket->status === 'cancelled' ? 'cancelled' : 'issued',
            'used_at' => null,
            'checkin_at' => null,
        ]);

        $payload = $this->qrCodeService->payloadJson($ticket);
        $ticket->update([
            'qr_payload' => $payload,
            'qr_code' => $this->qrCodeService->svgDataUri($payload),
        ]);

        CatminEventBus::dispatch('event.ticket.regenerated', [
            'event_id' => $ticket->event_id,
            'participant_id' => $ticket->event_participant_id,
            'ticket_id' => $ticket->id,
        ]);

        return $ticket->fresh() ?? $ticket;
    }

    private function nextUniqueCode(Event $event): string
    {
        do {
            $candidate = strtoupper('EVT-' . $event->id . '-' . Str::random(8));
            $exists = EventTicket::query()
                ->where('code', $candidate)
                ->orWhere('ticket_number', $candidate)
                ->exists();
        } while ($exists);

        return $candidate;
    }
}
