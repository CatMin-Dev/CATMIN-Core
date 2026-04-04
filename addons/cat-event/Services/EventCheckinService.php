<?php

declare(strict_types=1);

namespace Addons\CatEvent\Services;

use App\Services\CatminEventBus;
use Addons\CatEvent\Models\Event;
use Addons\CatEvent\Models\EventCheckin;
use Addons\CatEvent\Models\EventTicket;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Modules\Logger\Services\SystemLogService;

class EventCheckinService
{
    /**
     * @return array<string,int>
     */
    public function attendanceStats(Event $event): array
    {
        $totalIssued = $event->tickets()
            ->whereIn('status', ['issued', 'used'])
            ->count();

        $checkedIn = $event->tickets()->where('status', 'used')->count();

        return [
            'total_tickets' => $totalIssued,
            'checkins_done' => $checkedIn,
            'remaining' => max(0, $totalIssued - $checkedIn),
            'absents' => max(0, $totalIssued - $checkedIn),
        ];
    }

    public function resolveByCode(Event $event, string $inputCode): ?EventTicket
    {
        $code = trim($inputCode);

        if ($code === '') {
            return null;
        }

        // Accept either raw code or QR JSON payload.
        if (str_starts_with($code, '{')) {
            $decoded = json_decode($code, true);
            if (is_array($decoded) && isset($decoded['ticket_code'])) {
                $code = (string) $decoded['ticket_code'];
            }
        }

        return EventTicket::query()
            ->where('event_id', $event->id)
            ->where(function (Builder $query) use ($code): void {
                $query->where('code', $code)
                    ->orWhere('ticket_number', $code);
            })
            ->first();
    }

    /**
     * @param array<string,string> $filters
     */
    public function attendanceListing(Event $event, array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $status = trim((string) ($filters['status'] ?? ''));
        $term = trim((string) ($filters['q'] ?? ''));

        return $event->checkins()
            ->with('participant', 'ticket')
            ->when($status !== '', fn ($q) => $q->whereHas('ticket', fn ($t) => $t->where('status', $status)))
            ->when($term !== '', function ($q) use ($term): void {
                $like = '%' . $term . '%';
                $q->whereHas('participant', fn ($p) => $p
                    ->where('first_name', 'like', $like)
                    ->orWhere('last_name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                )
                ->orWhereHas('ticket', fn ($t) => $t
                    ->where('ticket_number', 'like', $like)
                    ->orWhere('code', 'like', $like)
                );
            })
            ->orderByDesc('checked_in_at')
            ->orderByDesc('checkin_at')
            ->paginate(50)
            ->withQueryString();
    }

    public function checkinByCode(
        Event $event,
        string $inputCode,
        string $method = 'manual',
        ?int $adminUserId = null,
        ?string $gate = null,
        ?string $notes = null
    ): EventCheckin {
        $this->assertEventAllowsCheckin($event);

        return DB::transaction(function () use ($event, $inputCode, $method, $adminUserId, $gate, $notes): EventCheckin {
            $ticket = $this->resolveByCode($event, $inputCode);

            if ($ticket === null) {
                $this->logAttempt('event.checkin.invalid', 'Billet introuvable', [
                    'event_id' => $event->id,
                    'code' => $inputCode,
                ]);
                CatminEventBus::dispatch('event.checkin.rejected', [
                    'event_id' => $event->id,
                    'reason' => 'ticket_not_found',
                    'code' => $inputCode,
                ]);
                throw new \RuntimeException('Billet introuvable pour cet événement.');
            }

            /** @var EventTicket $locked */
            $locked = EventTicket::query()->whereKey($ticket->id)->lockForUpdate()->firstOrFail();

            if (in_array($locked->status, ['cancelled', 'invalid'], true)) {
                $this->logAttempt('event.checkin.rejected', 'Billet annulé/invalide', [
                    'event_id' => $event->id,
                    'ticket_id' => $locked->id,
                    'status' => $locked->status,
                ]);
                CatminEventBus::dispatch('event.checkin.rejected', [
                    'event_id' => $event->id,
                    'ticket_id' => $locked->id,
                    'reason' => 'ticket_' . $locked->status,
                ]);
                throw new \RuntimeException('Ce billet est annulé ou invalide.');
            }

            if ($locked->status === 'used' || $locked->used_at !== null) {
                $this->logAttempt('event.checkin.duplicate', 'Double check-in refusé', [
                    'event_id' => $event->id,
                    'ticket_id' => $locked->id,
                    'used_at' => optional($locked->used_at)->toIso8601String(),
                ]);
                CatminEventBus::dispatch('event.checkin.duplicate', [
                    'event_id' => $event->id,
                    'ticket_id' => $locked->id,
                ]);
                throw new \RuntimeException('Ce billet a déjà été validé.');
            }

            $now = now();
            $locked->update([
                'status' => 'used',
                'used_at' => $now,
                'checkin_at' => $now,
            ]);

            /** @var EventCheckin $checkin */
            $checkin = EventCheckin::query()->create([
                'event_id' => $locked->event_id,
                'event_ticket_id' => $locked->id,
                'event_participant_id' => $locked->event_participant_id,
                'ticket_id' => $locked->id,
                'checked_in_by' => $adminUserId,
                'checked_in_at' => $now,
                'checkin_at' => $now,
                'checkin_method' => $method,
                'admin_user_id' => $adminUserId,
                'location' => $gate,
                'notes' => $notes,
            ]);

            $locked->participant()?->update(['status' => 'attended']);

            $this->logAttempt('event.checkin.done', 'Check-in validé', [
                'event_id' => $event->id,
                'ticket_id' => $locked->id,
                'checkin_id' => $checkin->id,
                'method' => $method,
            ]);

            CatminEventBus::dispatch('event.checkin.done', [
                'event_id' => $event->id,
                'ticket_id' => $locked->id,
                'participant_id' => $locked->event_participant_id,
                'checkin_id' => $checkin->id,
                'method' => $method,
            ]);

            return $checkin;
        });
    }

    private function assertEventAllowsCheckin(Event $event): void
    {
        if (in_array($event->status, ['cancelled', 'archived', 'finished', 'completed'], true)) {
            throw new \RuntimeException('Le check-in est fermé pour cet événement.');
        }
    }

    private function logAttempt(string $action, string $message, array $context = []): void
    {
        if (!class_exists(SystemLogService::class)) {
            return;
        }

        try {
            app(SystemLogService::class)->log('info', $message, array_merge(['action' => $action], $context));
        } catch (\Throwable) {
        }
    }
}
