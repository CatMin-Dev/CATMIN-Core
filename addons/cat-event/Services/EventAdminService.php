<?php

namespace Addons\CatEvent\Services;

use App\Services\CatminEventBus;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Logger\Services\SystemLogService;
use Modules\Mailer\Services\MailerAdminService;
use Addons\CatEvent\Models\Event;
use Addons\CatEvent\Models\EventSession;
use Addons\CatEvent\Models\EventParticipant;
use Addons\CatEvent\Models\EventTicket;
use Addons\CatEvent\Models\EventCheckin;
use Addons\CatEvent\Services\EventTicketService;
use Addons\CatEvent\Services\EventCheckinService;

class EventAdminService
{
    private bool $loggerAvailable;
    private bool $mailerAvailable;

    public function __construct()
    {
        $this->loggerAvailable = class_exists(SystemLogService::class);
        $this->mailerAvailable = class_exists(MailerAdminService::class);
    }

    // ─── Listing ───────────────────────────────────────────────────────────────

    /**
     * @param array<string, mixed> $filters
     */
    public function listing(array $filters = []): LengthAwarePaginator
    {
        return Event::query()
            ->withCount(['participants', 'tickets'])
            ->when(($filters['status'] ?? '') !== '', fn ($q) => $q->where('status', (string) $filters['status']))
            ->when(($filters['location'] ?? '') !== '', fn ($q) => $q->where('location', 'like', '%' . $filters['location'] . '%'))
            ->when(($filters['date_from'] ?? '') !== '', fn ($q) => $q->whereDate('start_at', '>=', $filters['date_from']))
            ->when(($filters['date_to'] ?? '') !== '', fn ($q) => $q->whereDate('start_at', '<=', $filters['date_to']))
            ->orderByDesc('start_at')
            ->paginate(25)
            ->withQueryString();
    }

    public function statuses(): array
    {
        return ['draft', 'published', 'sold_out', 'cancelled', 'archived', 'finished'];
    }

    public function participationModes(): array
    {
        return ['free_registration', 'approval_required', 'ticket_required', 'external_link', 'disabled'];
    }

    // ─── CRUD Event ────────────────────────────────────────────────────────────

    /**
     * @param array<string, mixed> $payload
     */
    public function create(array $payload): Event
    {
        $slug = $this->uniqueSlug((string) $payload['title'], (string) ($payload['slug'] ?? ''));

        /** @var Event $event */
        $event = DB::transaction(function () use ($payload, $slug) {
            return Event::query()->create($this->eventPayload($payload, $slug));
        });

        $this->logAudit('event.created', 'Événement créé', ['event_id' => $event->id, 'title' => $event->title]);

        return $event;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function update(Event $event, array $payload): Event
    {
        $slug = $this->uniqueSlug((string) $payload['title'], (string) ($payload['slug'] ?? ''), $event->id);

        DB::transaction(function () use ($event, $payload, $slug): void {
            $event->update($this->eventPayload($payload, $slug));
        });

        $this->logAudit('event.updated', 'Événement modifié', ['event_id' => $event->id, 'title' => $event->title]);

        return $event->fresh() ?? $event;
    }

    public function delete(Event $event): void
    {
        $id    = $event->id;
        $title = $event->title;

        $event->delete();

        $this->logAudit('event.deleted', 'Événement supprimé', ['event_id' => $id, 'title' => $title]);
    }

    public function toggleStatus(Event $event): Event
    {
        $newStatus = $event->status === 'published' ? 'draft' : 'published';
        $event->update([
            'status'       => $newStatus,
            'published_at' => $newStatus === 'published' ? now() : null,
        ]);

        $this->logAudit('event.status_toggled', 'Statut événement modifié', [
            'event_id'   => $event->id,
            'new_status' => $newStatus,
        ]);

        return $event->fresh() ?? $event;
    }

    // ─── Sessions ──────────────────────────────────────────────────────────────

    /**
     * @param array<string, mixed> $payload
     */
    public function createSession(Event $event, array $payload): EventSession
    {
        /** @var EventSession $session */
        $session = $event->sessions()->create([
            'title'    => (string) $payload['title'],
            'start_at' => (string) $payload['start_at'],
            'end_at'   => (string) $payload['end_at'],
            'location' => $payload['location'] ?? null,
            'capacity' => isset($payload['capacity']) ? (int) $payload['capacity'] : null,
            'notes'    => $payload['notes'] ?? null,
        ]);

        $this->logAudit('event.session.created', 'Session créée', [
            'event_id'   => $event->id,
            'session_id' => $session->id,
        ]);

        return $session;
    }

    public function deleteSession(EventSession $session): void
    {
        $session->delete();
    }

    // ─── Participants ──────────────────────────────────────────────────────────

    /**
     * @param array<string, mixed> $payload
     */
    public function registerParticipant(Event $event, array $payload): EventParticipant
    {
        /** @var EventParticipant $participant */
        $participant = DB::transaction(function () use ($event, $payload): EventParticipant {
            /** @var EventParticipant $p */
            $p = $event->participants()->create([
                'event_session_id' => $payload['event_session_id'] ?? null,
                'first_name'       => (string) $payload['first_name'],
                'last_name'        => $payload['last_name'] ?? null,
                'email'            => strtolower(trim((string) $payload['email'])),
                'phone'            => $payload['phone'] ?? null,
                'status'           => 'confirmed',
                'notes'            => $payload['notes'] ?? null,
                'registered_at'    => now(),
                'confirmed_at'     => now(),
            ]);

            $this->generateTicket($event, $p);

            return $p;
        });

        $this->logAudit('event.participant.registered', 'Participant inscrit', [
            'event_id'       => $event->id,
            'participant_id' => $participant->id,
            'email'          => $participant->email,
        ]);

        $this->sendConfirmationMail($event, $participant);

        return $participant;
    }

    public function updateParticipantStatus(EventParticipant $participant, string $status): EventParticipant
    {
        $participant->update([
            'status'       => $status,
            'confirmed_at' => $status === 'confirmed' ? now() : $participant->confirmed_at,
        ]);

        $this->logAudit('event.participant.status_changed', 'Statut participant modifié', [
            'participant_id' => $participant->id,
            'new_status'     => $status,
        ]);

        return $participant->fresh() ?? $participant;
    }

    public function deleteParticipant(EventParticipant $participant): void
    {
        $participant->delete();
    }

    // ─── Tickets ───────────────────────────────────────────────────────────────

    public function generateTicket(Event $event, EventParticipant $participant): EventTicket
    {
        /** @var EventTicketService $ticketService */
        $ticketService = app(EventTicketService::class);

        return $ticketService->issue($event, $participant, 'manual');
    }

    public function cancelTicket(EventTicket $ticket): EventTicket
    {
        $ticket->update([
            'status' => 'cancelled',
            'used_at' => null,
            'checkin_at' => null,
        ]);

        CatminEventBus::dispatch('event.ticket.cancelled', [
            'event_id' => $ticket->event_id,
            'participant_id' => $ticket->event_participant_id,
            'ticket_id' => $ticket->id,
        ]);

        $this->logAudit('event.ticket.cancelled', 'Billet annulé', ['ticket_id' => $ticket->id]);

        return $ticket->fresh() ?? $ticket;
    }

    // ─── Check-in ──────────────────────────────────────────────────────────────

    public function checkin(EventTicket $ticket, string $method = 'manual', ?int $adminUserId = null): EventCheckin
    {
        /** @var EventCheckinService $checkinService */
        $checkinService = app(EventCheckinService::class);

        $event = $ticket->event()->firstOrFail();

        return $checkinService->checkinByCode(
            $event,
            $ticket->publicCode(),
            $method,
            $adminUserId,
            null,
            null
        );
    }

    // ─── Helpers ───────────────────────────────────────────────────────────────

    private function uniqueSlug(string $title, string $slugInput, ?int $excludeId = null): string
    {
        $base  = $slugInput !== '' ? Str::slug($slugInput) : Str::slug($title);
        $slug  = $base;
        $index = 1;

        while (
            Event::query()
                ->where('slug', $slug)
                ->when($excludeId !== null, fn ($q) => $q->where('id', '!=', $excludeId))
                ->exists()
        ) {
            $slug = $base . '-' . $index++;
        }

        return $slug;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function eventPayload(array $payload, string $slug): array
    {
        return [
            'title'                 => (string) $payload['title'],
            'slug'                  => $slug,
            'description'           => $payload['description'] ?? null,
            'location'              => $payload['location'] ?? null,
            'address'               => $payload['address'] ?? null,
            'start_at'              => (string) $payload['start_at'],
            'end_at'                => (string) $payload['end_at'],
            'capacity'              => isset($payload['capacity']) && $payload['capacity'] !== '' ? (int) $payload['capacity'] : null,
            'status'                => (string) ($payload['status'] ?? 'draft'),
            'featured_image'        => $payload['featured_image'] ?? null,
            'organizer_name'        => $payload['organizer_name'] ?? null,
            'organizer_email'       => $payload['organizer_email'] ?? null,
            'is_free'               => (bool) ($payload['is_free'] ?? true),
            'ticket_price'          => (float) ($payload['ticket_price'] ?? 0),
            'registration_enabled'  => (bool) ($payload['registration_enabled'] ?? true),
            'participation_mode'    => (string) ($payload['participation_mode'] ?? 'free_registration'),
            'external_url'          => ($payload['external_url'] ?? '') !== '' ? (string) $payload['external_url'] : null,
            'allow_waitlist'        => (bool) ($payload['allow_waitlist'] ?? false),
            'max_places_per_registration' => max(1, (int) ($payload['max_places_per_registration'] ?? 1)),
            'registration_deadline' => ($payload['registration_deadline'] ?? '') !== '' ? (string) $payload['registration_deadline'] : null,
            'published_at'          => ($payload['status'] ?? 'draft') === 'published' ? now()->toDateTimeString() : null,
        ];
    }

    private function logAudit(string $action, string $message, array $context = []): void
    {
        if ($this->loggerAvailable) {
            try {
                app(SystemLogService::class)->log('info', $message, array_merge(['action' => $action], $context));
            } catch (\Throwable) {
                // non-blocking
            }
        }
    }

    private function sendConfirmationMail(Event $event, EventParticipant $participant): void
    {
        if (!$this->mailerAvailable) {
            return;
        }

        try {
            $mailer  = app(MailerAdminService::class);
            $subject = 'Confirmation d\'inscription — ' . $event->title;
            $body    = sprintf(
                "Bonjour %s,\n\nVotre inscription à l'événement \"%s\" (le %s) est confirmée.\n\nVotre numéro de billet : %s\n\nÀ bientôt !",
                $participant->fullName(),
                $event->title,
                $event->start_at->format('d/m/Y à H:i'),
                optional($participant->ticket)->ticket_number ?? 'N/A'
            );

            $mailer->sendRaw($participant->email, $subject, $body);
        } catch (\Throwable) {
            // non-blocking
        }
    }
}
