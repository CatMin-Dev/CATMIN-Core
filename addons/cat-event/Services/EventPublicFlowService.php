<?php

declare(strict_types=1);

namespace Addons\CatEvent\Services;

use Addons\CatEvent\Models\Event;
use Addons\CatminEventShopBridge\Models\EventShopTicketType;
use Illuminate\Support\Facades\Schema;

class EventPublicFlowService
{
    public function publicBySlug(string $slug): ?Event
    {
        return Event::query()
            ->where('slug', $slug)
            ->where('status', '!=', 'draft')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->first();
    }

    /**
     * @return array<string,mixed>
     */
    public function buildPublicState(Event $event): array
    {
        $remaining = $this->remainingCapacity($event);
        $status = $this->effectiveStatus($event, $remaining);

        $state = [
            'status' => $status,
            'remaining' => $remaining,
            'is_closed' => in_array($status, ['cancelled', 'archived', 'finished'], true),
            'is_sold_out' => $status === 'sold_out',
            'cta' => [
                'label' => null,
                'action' => 'none',
                'url' => null,
                'requires_shop' => false,
            ],
        ];

        if ($state['is_closed']) {
            return $state;
        }

        if ($status === 'sold_out') {
            if ((bool) $event->allow_waitlist) {
                $state['cta'] = [
                    'label' => 'Rejoindre la liste d\'attente',
                    'action' => 'register',
                    'url' => null,
                    'requires_shop' => false,
                ];
            }

            return $state;
        }

        if (!$event->registration_enabled || $this->registrationDeadlinePassed($event)) {
            return $state;
        }

        $mode = (string) ($event->participation_mode ?: 'free_registration');

        if ($mode === 'disabled') {
            return $state;
        }

        if ($mode === 'external_link') {
            $state['cta'] = [
                'label' => 'Acceder a l\'inscription',
                'action' => 'external',
                'url' => $event->external_url,
                'requires_shop' => false,
            ];

            return $state;
        }

        if ($mode === 'ticket_required') {
            $shopUrl = $this->resolveShopUrl($event);
            $state['cta'] = [
                'label' => 'Acheter un billet',
                'action' => $shopUrl ? 'shop' : 'none',
                'url' => $shopUrl,
                'requires_shop' => true,
            ];

            return $state;
        }

        $state['cta'] = [
            'label' => $mode === 'approval_required' ? 'Demander une preinscription' : 'S\'inscrire',
            'action' => 'register',
            'url' => null,
            'requires_shop' => false,
        ];

        return $state;
    }

    public function remainingCapacity(Event $event): ?int
    {
        if ($event->capacity === null) {
            return null;
        }

        $usedSeats = (int) $event->participants()
            ->whereIn('status', ['approved', 'confirmed', 'ticketed', 'attended'])
            ->sum('seats_count');

        return max(0, (int) $event->capacity - $usedSeats);
    }

    public function effectiveStatus(Event $event, ?int $remainingCapacity = null): string
    {
        if ($event->status === 'cancelled') {
            return 'cancelled';
        }

        if ($event->status === 'archived') {
            return 'archived';
        }

        if (in_array($event->status, ['finished', 'completed'], true) || ($event->end_at !== null && $event->end_at->isPast())) {
            return 'finished';
        }

        if ($event->status === 'sold_out') {
            return 'sold_out';
        }

        if ($remainingCapacity !== null && $remainingCapacity <= 0) {
            return 'sold_out';
        }

        return 'published';
    }

    public function registrationDeadlinePassed(Event $event): bool
    {
        return $event->registration_deadline !== null && $event->registration_deadline->isPast();
    }

    private function resolveShopUrl(Event $event): ?string
    {
        if (!class_exists(EventShopTicketType::class)) {
            return null;
        }

        if (!Schema::hasTable('event_shop_bridge_ticket_types')) {
            return null;
        }

        /** @var EventShopTicketType|null $ticketType */
        $ticketType = EventShopTicketType::query()
            ->where('event_id', $event->id)
            ->where('status', 'active')
            ->whereNotNull('shop_product_id')
            ->first();

        if ($ticketType === null || $ticketType->shop_product_id === null) {
            return null;
        }

        $pattern = (string) config('cat_event.shop_redirect_pattern', '/shop/products/{product_id}');

        return url(str_replace('{product_id}', (string) $ticketType->shop_product_id, $pattern));
    }
}
