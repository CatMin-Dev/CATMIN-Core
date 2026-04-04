<?php

declare(strict_types=1);

namespace Addons\CatEvent\Services;

use Addons\CatEvent\Models\Event;
use Addons\CatEvent\Models\EventParticipant;
use App\Services\CatminEventBus;
use Illuminate\Support\Facades\DB;
use Modules\Logger\Services\SystemLogService;
use Modules\Mailer\Services\MailerAdminService;

class EventRegistrationService
{
    public function __construct(
        private readonly EventPublicFlowService $publicFlowService,
        private readonly EventTicketService $ticketService,
    ) {
    }

    /**
     * @param array<string,mixed> $payload
     */
    public function register(Event $event, array $payload): EventParticipant
    {
        $state = $this->publicFlowService->buildPublicState($event);

        if (($state['cta']['action'] ?? 'none') !== 'register') {
            throw new \RuntimeException('Les inscriptions sont fermees pour cet evenement.');
        }

        return DB::transaction(function () use ($event, $payload): EventParticipant {
            $name = trim((string) ($payload['name'] ?? ''));
            [$firstName, $lastName] = $this->splitName($name);
            $email = strtolower(trim((string) $payload['email']));
            $seatsCount = max(1, (int) ($payload['seats_count'] ?? 1));
            $idempotencyKey = $this->idempotencyKey($event, $email, (string) ($payload['form_token'] ?? ''));

            $alreadyRegistered = EventParticipant::query()
                ->where('event_id', $event->id)
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($alreadyRegistered !== null) {
                return $alreadyRegistered;
            }

            $status = $this->resolveParticipantStatus($event);

            $remainingCapacity = $this->publicFlowService->remainingCapacity($event);
            if ($remainingCapacity !== null && $seatsCount > $remainingCapacity) {
                if ((bool) $event->allow_waitlist) {
                    $status = 'waitlisted';
                    $seatsCount = 1;
                } else {
                    throw new \RuntimeException('Capacite atteinte pour cet evenement.');
                }
            }

            /** @var EventParticipant $participant */
            $participant = EventParticipant::query()->create([
                'event_id' => $event->id,
                'event_session_id' => null,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'phone' => $payload['phone'] ?? null,
                'seats_count' => $seatsCount,
                'status' => $status,
                'source' => 'public_form',
                'idempotency_key' => $idempotencyKey,
                'notes' => $payload['notes'] ?? null,
                'registered_at' => now(),
                'confirmed_at' => in_array($status, ['approved', 'confirmed', 'ticketed'], true) ? now() : null,
            ]);

            if (in_array($status, ['confirmed', 'ticketed'], true)) {
                $this->ticketService->issue($event, $participant, 'public');
            }

            $this->sendRegistrationMail($event, $participant);
            $this->emitAndLog($event, $participant);

            return $participant;
        });
    }

    private function resolveParticipantStatus(Event $event): string
    {
        $mode = (string) ($event->participation_mode ?: 'free_registration');

        return match ($mode) {
            'approval_required' => 'pending',
            'ticket_required' => 'ticketed',
            default => 'confirmed',
        };
    }

    /**
     * @return array{0:string,1:?string}
     */
    private function splitName(string $name): array
    {
        $name = preg_replace('/\s+/', ' ', trim($name)) ?? '';
        if ($name === '') {
            return ['Participant', null];
        }

        $parts = explode(' ', $name, 2);

        return [$parts[0], $parts[1] ?? null];
    }

    private function idempotencyKey(Event $event, string $email, string $formToken): string
    {
        $token = $formToken !== '' ? $formToken : session()->getId();

        return hash('sha256', $event->id . '|' . $email . '|' . $token);
    }

    private function sendRegistrationMail(Event $event, EventParticipant $participant): void
    {
        if (!class_exists(MailerAdminService::class)) {
            return;
        }

        try {
            $subject = 'Inscription evenement - ' . $event->title;
            $body = "Bonjour {$participant->fullName()},\n\n";
            $body .= "Votre demande pour l'evenement \"{$event->title}\" est en statut: {$participant->status}.\n";
            $body .= 'Date: ' . optional($event->start_at)->format('d/m/Y H:i') . "\n\n";
            $body .= "Merci.\n";

            app(MailerAdminService::class)->sendRaw($participant->email, $subject, $body);
        } catch (\Throwable) {
            // Keep public registration resilient.
        }
    }

    private function emitAndLog(Event $event, EventParticipant $participant): void
    {
        CatminEventBus::dispatch('event.participant.public_registered', [
            'event_id' => $event->id,
            'participant_id' => $participant->id,
            'status' => $participant->status,
            'source' => 'public',
        ]);

        if (!class_exists(SystemLogService::class)) {
            return;
        }

        try {
            app(SystemLogService::class)->log('info', 'Inscription publique evenement', [
                'action' => 'event.participant.public_registered',
                'event_id' => $event->id,
                'participant_id' => $participant->id,
                'status' => $participant->status,
            ]);
        } catch (\Throwable) {
        }
    }
}
