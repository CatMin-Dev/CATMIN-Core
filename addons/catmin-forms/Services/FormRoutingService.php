<?php

namespace Addons\CatminForms\Services;

use Addons\CatminForms\Models\FormDefinition;
use Addons\CatminForms\Models\FormSubmission;
use Illuminate\Support\Facades\DB;
use Modules\Logger\Services\SystemLogService;

class FormRoutingService
{
    /** @param array<string,mixed> $payload */
    public function handle(FormDefinition $formDefinition, FormSubmission $submission, array $payload): ?int
    {
        $mapping = (string) (($formDefinition->config['mapping'] ?? 'none'));
        $targetId = (int) (($formDefinition->config['target_id'] ?? 0));

        return match ($mapping) {
            'crm_lead' => $this->mapToCrm($payload),
            'event_preregistration' => $this->mapToEvent($targetId, $payload),
            'booking_request' => $this->mapToBooking($targetId, $payload),
            default => null,
        };
    }

    /** @param array<string,mixed> $payload */
    private function mapToCrm(array $payload): ?int
    {
        if (!class_exists(\Addons\CatminCrmLight\Models\CrmContact::class) || !DB::getSchemaBuilder()->hasTable('crm_contacts')) {
            return null;
        }

        $name = trim((string) ($payload['name'] ?? ''));
        [$firstName, $lastName] = array_pad(explode(' ', $name, 2), 2, null);

        $contact = \Addons\CatminCrmLight\Models\CrmContact::query()->create([
            'first_name' => $firstName ?: 'Lead',
            'last_name' => $lastName,
            'email' => strtolower(trim((string) ($payload['email'] ?? ''))),
            'phone' => $payload['phone'] ?? null,
            'status' => 'lead',
            'pipeline_stage' => 'new',
            'source' => 'forms',
            'notes' => (string) ($payload['message'] ?? ''),
            'metadata' => ['source' => 'forms'],
        ]);

        return (int) $contact->id;
    }

    /** @param array<string,mixed> $payload */
    private function mapToEvent(int $eventId, array $payload): ?int
    {
        if ($eventId <= 0 || !DB::getSchemaBuilder()->hasTable('event_participants')) {
            return null;
        }

        $name = trim((string) ($payload['name'] ?? ''));
        [$firstName, $lastName] = array_pad(explode(' ', $name, 2), 2, null);

        $id = DB::table('event_participants')->insertGetId([
            'event_id' => $eventId,
            'first_name' => $firstName ?: 'Participant',
            'last_name' => $lastName,
            'email' => strtolower(trim((string) ($payload['email'] ?? ''))),
            'phone' => $payload['phone'] ?? null,
            'status' => 'pending',
            'source' => 'forms',
            'seats_count' => 1,
            'registered_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return (int) $id;
    }

    /** @param array<string,mixed> $payload */
    private function mapToBooking(int $serviceId, array $payload): ?int
    {
        if ($serviceId <= 0 || !DB::getSchemaBuilder()->hasTable('booking_services') || !DB::getSchemaBuilder()->hasTable('bookings')) {
            return null;
        }

        $slotId = DB::table('booking_slots')
            ->where('booking_service_id', $serviceId)
            ->where('is_active', true)
            ->where('start_at', '>=', now())
            ->orderBy('start_at')
            ->value('id');

        if ($slotId === null) {
            return null;
        }

        $id = DB::table('bookings')->insertGetId([
            'booking_service_id' => $serviceId,
            'booking_slot_id' => $slotId,
            'status' => 'pending',
            'customer_name' => (string) ($payload['name'] ?? 'Lead booking'),
            'customer_email' => strtolower(trim((string) ($payload['email'] ?? ''))),
            'customer_phone' => $payload['phone'] ?? null,
            'notes' => (string) ($payload['message'] ?? ''),
            'confirmation_code' => strtoupper('BK-' . \Illuminate\Support\Str::random(10)),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        try {
            if (class_exists(SystemLogService::class)) {
                app(SystemLogService::class)->log('info', 'Booking request from form', [
                    'booking_id' => $id,
                    'service_id' => $serviceId,
                ]);
            }
        } catch (\Throwable) {
        }

        return (int) $id;
    }
}
