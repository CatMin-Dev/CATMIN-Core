<?php

namespace Addons\CatminBooking\Services;

use Addons\CatminBooking\Models\Booking;
use Addons\CatminBooking\Models\BookingService;
use Addons\CatminBooking\Models\BookingSlot;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Modules\Cache\Services\QueryCacheService;
use Modules\Webhooks\Services\WebhookDispatcher;

class BookingAdminService
{
    /**
     * @param array<string, mixed> $filters
     */
    public function services(array $filters = []): LengthAwarePaginator
    {
        $page = max(1, (int) request()->query('page', 1));
        $key = 'services.' . md5(json_encode([$filters, $page]));

        return QueryCacheService::remember('booking', $key, 90, function () use ($filters): LengthAwarePaginator {
            return BookingService::query()
                ->when(($filters['q'] ?? '') !== '', fn ($q) => $q->where('name', 'like', '%' . $filters['q'] . '%'))
                ->orderByDesc('created_at')
                ->paginate(25)
                ->withQueryString();
        });
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function createService(array $payload): BookingService
    {
        $slugBase = trim((string) ($payload['slug'] ?? '')) !== ''
            ? (string) $payload['slug']
            : (string) $payload['name'];

        $created = BookingService::query()->create([
            'name' => (string) $payload['name'],
            'slug' => $this->uniqueServiceSlug($slugBase),
            'description' => $payload['description'] ?? null,
            'duration_minutes' => (int) $payload['duration_minutes'],
            'price_cents' => (int) round(((float) ($payload['price'] ?? 0)) * 100),
            'is_active' => (bool) ($payload['is_active'] ?? true),
            'metadata' => [
                'created_by' => auth()->id(),
            ],
        ]);

        $this->invalidateCache();

        return $created;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function updateService(BookingService $service, array $payload): BookingService
    {
        $slugBase = trim((string) ($payload['slug'] ?? '')) !== ''
            ? (string) $payload['slug']
            : (string) $payload['name'];

        $service->update([
            'name' => (string) $payload['name'],
            'slug' => $this->uniqueServiceSlug($slugBase, (int) $service->id),
            'description' => $payload['description'] ?? null,
            'duration_minutes' => (int) $payload['duration_minutes'],
            'price_cents' => (int) round(((float) ($payload['price'] ?? 0)) * 100),
            'is_active' => (bool) ($payload['is_active'] ?? true),
        ]);

        $this->invalidateCache();

        return $service->fresh() ?? $service;
    }

    public function deleteService(BookingService $service): void
    {
        $service->delete();
        $this->invalidateCache();
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function slots(array $filters = []): LengthAwarePaginator
    {
        $page = max(1, (int) request()->query('page', 1));
        $key = 'slots.' . md5(json_encode([$filters, $page]));

        return QueryCacheService::remember('booking', $key, 90, function () use ($filters): LengthAwarePaginator {
            return BookingSlot::query()
                ->with('service:id,name,slug')
                ->when(!empty($filters['booking_service_id']), fn ($q) => $q->where('booking_service_id', (int) $filters['booking_service_id']))
                ->when(($filters['from'] ?? '') !== '', fn ($q) => $q->whereDate('start_at', '>=', (string) $filters['from']))
                ->when(($filters['to'] ?? '') !== '', fn ($q) => $q->whereDate('start_at', '<=', (string) $filters['to']))
                ->orderBy('start_at')
                ->paginate(40)
                ->withQueryString();
        });
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function createSlot(array $payload): BookingSlot
    {
        $startAt = (string) $payload['start_at'];
        $endAt = (string) $payload['end_at'];

        $this->assertNoSlotCollision((int) $payload['booking_service_id'], $startAt, $endAt);

        $created = BookingSlot::query()->create([
            'booking_service_id' => (int) $payload['booking_service_id'],
            'start_at' => $startAt,
            'end_at' => $endAt,
            'capacity' => max(1, (int) $payload['capacity']),
            'booked_count' => 0,
            'is_active' => (bool) ($payload['is_active'] ?? true),
        ]);

        $this->invalidateCache();

        return $created;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function updateSlot(BookingSlot $slot, array $payload): BookingSlot
    {
        $serviceId = (int) ($payload['booking_service_id'] ?? $slot->booking_service_id);
        $startAt = (string) ($payload['start_at'] ?? $slot->start_at?->toDateTimeString());
        $endAt = (string) ($payload['end_at'] ?? $slot->end_at?->toDateTimeString());

        $this->assertNoSlotCollision($serviceId, $startAt, $endAt, (int) $slot->id);

        $slot->update([
            'booking_service_id' => $serviceId,
            'start_at' => $startAt,
            'end_at' => $endAt,
            'capacity' => max((int) $slot->booked_count, (int) ($payload['capacity'] ?? $slot->capacity)),
            'is_active' => (bool) ($payload['is_active'] ?? $slot->is_active),
        ]);

        $this->invalidateCache();

        return $slot->fresh() ?? $slot;
    }

    public function deleteSlot(BookingSlot $slot): void
    {
        if ((int) $slot->booked_count > 0) {
            throw new \RuntimeException('Impossible de supprimer un créneau avec des réservations.');
        }

        $slot->delete();
        $this->invalidateCache();
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function bookings(array $filters = []): LengthAwarePaginator
    {
        $page = max(1, (int) request()->query('page', 1));
        $key = 'bookings.' . md5(json_encode([$filters, $page]));

        return QueryCacheService::remember('booking', $key, 90, function () use ($filters): LengthAwarePaginator {
            return Booking::query()
                ->with(['service:id,name,slug', 'slot:id,booking_service_id,start_at,end_at'])
                ->when(($filters['status'] ?? '') !== '', fn ($q) => $q->where('status', (string) $filters['status']))
                ->when(!empty($filters['booking_service_id']), fn ($q) => $q->where('booking_service_id', (int) $filters['booking_service_id']))
                ->when(($filters['from'] ?? '') !== '', fn ($q) => $q->whereDate('created_at', '>=', (string) $filters['from']))
                ->when(($filters['to'] ?? '') !== '', fn ($q) => $q->whereDate('created_at', '<=', (string) $filters['to']))
                ->orderByDesc('created_at')
                ->paginate(30)
                ->withQueryString();
        });
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function createBooking(array $payload): Booking
    {
        return DB::transaction(function () use ($payload): Booking {
            /** @var BookingSlot $slot */
            $slot = BookingSlot::query()->lockForUpdate()->findOrFail((int) $payload['booking_slot_id']);

            if (!$slot->is_active) {
                throw new \RuntimeException('Ce créneau est inactif.');
            }

            if ($slot->remainingCapacity() <= 0) {
                throw new \RuntimeException('Ce créneau est complet.');
            }

            /** @var Booking $booking */
            $booking = Booking::query()->create([
                'booking_service_id' => (int) $slot->booking_service_id,
                'booking_slot_id' => (int) $slot->id,
                'status' => (string) ($payload['status'] ?? 'pending'),
                'customer_name' => (string) $payload['customer_name'],
                'customer_email' => strtolower(trim((string) $payload['customer_email'])),
                'customer_phone' => $payload['customer_phone'] ?? null,
                'notes' => $payload['notes'] ?? null,
                'internal_note' => $payload['internal_note'] ?? null,
                'confirmation_code' => strtoupper('BK-' . Str::random(10)),
                'confirmed_at' => (($payload['status'] ?? 'pending') === 'confirmed') ? now() : null,
                'cancelled_at' => (($payload['status'] ?? 'pending') === 'cancelled') ? now() : null,
            ]);

            $slot->update([
                'booked_count' => ((int) $slot->booked_count) + 1,
            ]);

            $this->dispatchWebhook('booking.created', $booking);
            $this->sendBookingMail($booking, 'created');

            $this->invalidateCache();

            return $booking;
        });
    }

    public function updateBookingStatus(Booking $booking, string $status, ?string $internalNote = null): Booking
    {
        if (!in_array($status, ['pending', 'confirmed', 'cancelled'], true)) {
            throw new \InvalidArgumentException('Statut de réservation invalide.');
        }

        DB::transaction(function () use ($booking, $status, $internalNote): void {
            $previousStatus = (string) $booking->status;

            if ($previousStatus === 'cancelled' && $status !== 'cancelled') {
                $slot = BookingSlot::query()->lockForUpdate()->findOrFail((int) $booking->booking_slot_id);
                if ($slot->remainingCapacity() <= 0) {
                    throw new \RuntimeException('Impossible de réactiver la réservation: créneau complet.');
                }
                $slot->update(['booked_count' => ((int) $slot->booked_count) + 1]);
            }

            if ($previousStatus !== 'cancelled' && $status === 'cancelled') {
                $slot = BookingSlot::query()->lockForUpdate()->findOrFail((int) $booking->booking_slot_id);
                $slot->update(['booked_count' => max(0, ((int) $slot->booked_count) - 1)]);
            }

            $booking->update([
                'status' => $status,
                'internal_note' => $internalNote,
                'confirmed_at' => $status === 'confirmed' ? now() : $booking->confirmed_at,
                'cancelled_at' => $status === 'cancelled' ? now() : null,
            ]);
        });

        $booking = $booking->fresh() ?? $booking;

        if ($status === 'confirmed') {
            $this->dispatchWebhook('booking.confirmed', $booking);
            $this->sendBookingMail($booking, 'confirmed');
        } elseif ($status === 'cancelled') {
            $this->dispatchWebhook('booking.cancelled', $booking);
            $this->sendBookingMail($booking, 'cancelled');
        }

        $this->invalidateCache();

        return $booking;
    }

    /**
     * @return array<int, string>
     */
    public function statuses(): array
    {
        return ['pending', 'confirmed', 'cancelled'];
    }

    /**
     * @return array<string, mixed>
     */
    public function calendarData(string $from, string $to): array
    {
        $key = 'calendar.' . md5($from . '|' . $to);

        return QueryCacheService::remember('booking', $key, 60, function () use ($from, $to): array {
            $slots = BookingSlot::query()
                ->with('service:id,name')
                ->whereBetween('start_at', [$from, $to])
                ->orderBy('start_at')
                ->get();

            return [
                'slots' => $slots->map(fn (BookingSlot $slot) => [
                    'id' => (int) $slot->id,
                    'service_id' => (int) $slot->booking_service_id,
                    'service_name' => (string) ($slot->service->name ?? ''),
                    'start_at' => optional($slot->start_at)?->toIso8601String(),
                    'end_at' => optional($slot->end_at)?->toIso8601String(),
                    'capacity' => (int) $slot->capacity,
                    'booked_count' => (int) $slot->booked_count,
                    'remaining' => $slot->remainingCapacity(),
                    'is_active' => (bool) $slot->is_active,
                ])->values()->all(),
            ];
        });
    }

    private function invalidateCache(): void
    {
        QueryCacheService::invalidateModules(['booking', 'dashboard', 'performance', 'crm']);
    }

    private function uniqueServiceSlug(string $base, ?int $excludeId = null): string
    {
        $slug = Str::slug($base);
        if ($slug === '') {
            $slug = 'service';
        }

        $candidate = $slug;
        $inc = 1;

        while (
            BookingService::query()
                ->where('slug', $candidate)
                ->when($excludeId !== null, fn ($q) => $q->where('id', '!=', $excludeId))
                ->exists()
        ) {
            $inc++;
            $candidate = $slug . '-' . $inc;
        }

        return $candidate;
    }

    private function assertNoSlotCollision(int $serviceId, string $startAt, string $endAt, ?int $excludeSlotId = null): void
    {
        $conflict = BookingSlot::query()
            ->where('booking_service_id', $serviceId)
            ->when($excludeSlotId !== null, fn ($q) => $q->where('id', '!=', $excludeSlotId))
            ->where(function ($q) use ($startAt, $endAt): void {
                $q->whereBetween('start_at', [$startAt, $endAt])
                  ->orWhereBetween('end_at', [$startAt, $endAt])
                  ->orWhere(function ($inner) use ($startAt, $endAt): void {
                      $inner->where('start_at', '<=', $startAt)
                            ->where('end_at', '>=', $endAt);
                  });
            })
            ->exists();

        if ($conflict) {
            throw new \RuntimeException('Conflit de créneau détecté pour ce service.');
        }
    }

    private function sendBookingMail(Booking $booking, string $type): void
    {
        if (!(bool) config('booking.mail_notifications', true)) {
            return;
        }

        $booking->loadMissing(['service:id,name', 'slot:id,start_at,end_at']);

        $subject = match ($type) {
            'confirmed' => 'Réservation confirmée',
            'cancelled' => 'Réservation annulée',
            default => 'Réservation reçue',
        };

        $body = "Bonjour {$booking->customer_name},\n\n";
        $body .= "Votre réservation ({$booking->confirmation_code}) pour le service \"" . ($booking->service->name ?? 'N/A') . "\" est actuellement : {$booking->status}.\n";
        $body .= 'Créneau : ' . optional($booking->slot->start_at)->format('d/m/Y H:i') . ' - ' . optional($booking->slot->end_at)->format('H:i') . "\n\n";
        $body .= "Merci.\n";

        try {
            Mail::raw($body, function ($message) use ($booking, $subject): void {
                $message->to($booking->customer_email, $booking->customer_name)
                    ->subject($subject);
            });
        } catch (\Throwable) {
            // Keep booking flow resilient if mail transport is unavailable.
        }
    }

    private function dispatchWebhook(string $event, Booking $booking): void
    {
        try {
            if (class_exists(WebhookDispatcher::class)) {
                WebhookDispatcher::dispatch($event, [
                    'event_id' => 'booking_' . $booking->id . '_' . now()->timestamp,
                    'booking_id' => (int) $booking->id,
                    'service_id' => (int) $booking->booking_service_id,
                    'slot_id' => (int) $booking->booking_slot_id,
                    'status' => (string) $booking->status,
                    'customer_email' => (string) $booking->customer_email,
                ]);
            }
        } catch (\Throwable) {
            // non-critical
        }
    }
}
