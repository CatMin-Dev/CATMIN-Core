<?php

namespace Addons\CatminEventShopBridge\Services;

use App\Services\CatminEventBus;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Addons\CatEvent\Models\Event;
use Addons\CatEvent\Models\EventParticipant;
use Addons\CatEvent\Models\EventTicket;
use Addons\CatEvent\Services\EventAdminService;
use Addons\CatminShop\Models\Order;
use Addons\CatminShop\Models\OrderItem;
use Addons\CatminShop\Models\Product;
use Addons\CatminShop\Services\ShopAdminService;
use Addons\CatminEventShopBridge\Models\EventShopOrderLink;
use Addons\CatminEventShopBridge\Models\EventShopTicketType;
use Modules\Logger\Services\SystemLogService;

class EventShopBridgeService
{
    private bool $loggerAvailable;

    public function __construct(
        private readonly ShopAdminService $shopAdminService,
        private readonly EventAdminService $eventAdminService,
    ) {
        $this->loggerAvailable = class_exists(SystemLogService::class);
    }

    public function ticketTypes(): LengthAwarePaginator
    {
        return EventShopTicketType::query()
            ->with(['event', 'product'])
            ->orderByDesc('id')
            ->paginate(25);
    }

    public function recentOrderLinks(): \Illuminate\Support\Collection
    {
        return EventShopOrderLink::query()
            ->with(['ticketType', 'ticket', 'order'])
            ->orderByDesc('id')
            ->limit(20)
            ->get();
    }

    public function events(): \Illuminate\Database\Eloquent\Collection
    {
        return Event::query()->orderByDesc('start_at')->get();
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function createTicketType(array $payload): EventShopTicketType
    {
        /** @var Event $event */
        $event = Event::query()->findOrFail((int) $payload['event_id']);

        $baseSlug = Str::slug((string) $payload['name']);
        $slug = $baseSlug !== '' ? $baseSlug : 'ticket';
        $sku = $payload['sku'] ?? strtoupper('EVT-' . $event->id . '-' . Str::upper(Str::random(6)));

        /** @var EventShopTicketType $ticketType */
        $ticketType = DB::transaction(function () use ($payload, $event, $slug, $sku) {
            $ticketType = EventShopTicketType::query()->create([
                'event_id' => $event->id,
                'name' => (string) $payload['name'],
                'slug' => $slug,
                'sku' => (string) $sku,
                'price' => (float) $payload['price'],
                'allocation' => ($payload['allocation'] ?? '') !== '' ? (int) $payload['allocation'] : null,
                'auto_cancel_on_order_cancel' => (bool) ($payload['auto_cancel_on_order_cancel'] ?? true),
                'status' => (string) ($payload['status'] ?? 'active'),
                'description' => (string) ($payload['description'] ?? ''),
            ]);

            $product = $this->shopAdminService->create([
                'name' => $event->title . ' — ' . (string) $payload['name'],
                'slug' => 'event-' . $event->slug . '-' . $slug,
                'sku' => $sku,
                'price' => (float) $payload['price'],
                'compare_at_price' => null,
                'description' => (string) ($payload['description'] ?? $event->description ?? ''),
                'stock_quantity' => $this->remainingCapacity($ticketType),
                'low_stock_threshold' => 0,
                'status' => 'active',
                'visibility' => 'public',
                'manage_stock' => true,
                'image_path' => $event->featured_image,
                'product_type' => 'digital',
                'category_ids' => [],
            ]);

            $ticketType->shop_product_id = $product->id;
            $ticketType->save();

            return $ticketType->fresh(['event', 'product']) ?? $ticketType;
        });

        $this->syncTicketTypeInventory($ticketType);
        $this->log('bridge.event_shop.ticket_type.created', 'Type de billet bridge cree', [
            'ticket_type_id' => $ticketType->id,
            'event_id' => $ticketType->event_id,
            'shop_product_id' => $ticketType->shop_product_id,
        ]);
        CatminEventBus::dispatch('bridge.event_shop.ticket_type.created', [
            'ticket_type_id' => $ticketType->id,
            'event_id' => $ticketType->event_id,
            'shop_product_id' => $ticketType->shop_product_id,
        ]);

        return $ticketType;
    }

    public function syncTicketTypeInventory(EventShopTicketType $ticketType): EventShopTicketType
    {
        $ticketType->loadMissing(['event', 'product']);

        if ($ticketType->product !== null) {
            $ticketType->product->update([
                'price' => (float) $ticketType->price,
                'stock_quantity' => $this->remainingCapacity($ticketType),
                'manage_stock' => true,
                'product_type' => 'digital',
                'status' => $ticketType->status === 'active' ? 'active' : 'inactive',
            ]);
        }

        $this->log('bridge.event_shop.ticket_type.synced', 'Stock bridge synchronise', [
            'ticket_type_id' => $ticketType->id,
            'remaining' => $this->remainingCapacity($ticketType),
        ]);
        CatminEventBus::dispatch('bridge.event_shop.ticket_type.synced', [
            'ticket_type_id' => $ticketType->id,
            'remaining' => $this->remainingCapacity($ticketType),
        ]);

        return $ticketType->fresh(['event', 'product']) ?? $ticketType;
    }

    public function handlePaidOrder(int $orderId): void
    {
        /** @var Order|null $order */
        $order = Order::query()->with(['items.product'])->find($orderId);
        if ($order === null) {
            return;
        }

        foreach ($order->items as $item) {
            $ticketType = EventShopTicketType::query()
                ->where('shop_product_id', $item->product_id)
                ->where('status', 'active')
                ->first();

            if (!$ticketType) {
                continue;
            }

            for ($unitIndex = 1; $unitIndex <= (int) $item->quantity; $unitIndex++) {
                $this->issueTicketForOrderUnit($order, $item, $ticketType, $unitIndex);
            }

            $this->syncTicketTypeInventory($ticketType);
        }
    }

    public function handleCancelledOrder(int $orderId): void
    {
        /** @var Order|null $order */
        $order = Order::query()->find($orderId);
        if ($order === null) {
            return;
        }

        $links = EventShopOrderLink::query()
            ->with(['ticketType', 'ticket', 'participant'])
            ->where('shop_order_id', $order->id)
            ->whereIn('status', ['issued', 'pending', 'failed_capacity'])
            ->get();

        foreach ($links as $link) {
            if ($link->ticketType && !$link->ticketType->auto_cancel_on_order_cancel) {
                continue;
            }

            DB::transaction(function () use ($link): void {
                if ($link->ticket && $link->ticket->status !== 'cancelled') {
                    $this->eventAdminService->cancelTicket($link->ticket);
                }

                if ($link->participant && $link->participant->status !== 'cancelled') {
                    $this->eventAdminService->updateParticipantStatus($link->participant, 'cancelled');
                }

                $link->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                    'integration_error' => null,
                ]);
            });

            if ($link->ticketType) {
                $this->syncTicketTypeInventory($link->ticketType);
            }

            $this->log('bridge.event_shop.ticket.cancelled', 'Billet bridge annule apres annulation commande', [
                'link_id' => $link->id,
                'order_id' => $link->shop_order_id,
            ]);
            CatminEventBus::dispatch('bridge.event_shop.ticket.cancelled', [
                'link_id' => $link->id,
                'order_id' => $link->shop_order_id,
            ]);
        }
    }

    private function issueTicketForOrderUnit(Order $order, OrderItem $item, EventShopTicketType $ticketType, int $unitIndex): void
    {
        $sourceKey = $order->id . ':' . $item->id . ':' . $unitIndex;

        /** @var EventShopOrderLink $link */
        $link = EventShopOrderLink::query()->firstOrCreate(
            ['source_key' => $sourceKey],
            [
                'ticket_type_id' => $ticketType->id,
                'shop_order_id' => $order->id,
                'shop_order_item_id' => $item->id,
                'event_id' => $ticketType->event_id,
                'unit_index' => $unitIndex,
                'status' => 'pending',
                'customer_email' => $order->customer_email,
                'customer_name' => $order->customer_name,
            ]
        );

        if (in_array($link->status, ['issued', 'cancelled'], true) && $link->event_ticket_id !== null) {
            return;
        }

        if ($this->remainingCapacity($ticketType) <= 0) {
            $link->update([
                'status' => 'failed_capacity',
                'integration_error' => 'Capacite evenement/billet epuisee.',
            ]);
            $this->log('bridge.event_shop.capacity.exhausted', 'Capacite epuisee pour emission billet bridge', [
                'order_id' => $order->id,
                'ticket_type_id' => $ticketType->id,
                'source_key' => $sourceKey,
            ]);
            CatminEventBus::dispatch('bridge.event_shop.capacity.exhausted', [
                'order_id' => $order->id,
                'ticket_type_id' => $ticketType->id,
                'source_key' => $sourceKey,
            ]);

            return;
        }

        [$firstName, $lastName] = array_pad(explode(' ', trim((string) $order->customer_name), 2), 2, null);

        DB::transaction(function () use ($ticketType, $order, $link, $firstName, $lastName): void {
            $participant = $this->eventAdminService->registerParticipant($ticketType->event, [
                'first_name' => $firstName ?: $order->customer_email,
                'last_name' => $lastName,
                'email' => $order->customer_email,
                'phone' => null,
                'notes' => 'Bridge order ' . $order->order_number,
            ]);

            /** @var EventTicket|null $ticket */
            $ticket = EventTicket::query()->where('event_participant_id', $participant->id)->latest('id')->first();

            $link->update([
                'event_participant_id' => $participant->id,
                'event_ticket_id' => $ticket?->id,
                'status' => $ticket ? 'issued' : 'pending',
                'issued_at' => $ticket ? now() : null,
                'integration_error' => $ticket ? null : 'Ticket non genere.',
            ]);
        });

        $this->log('bridge.event_shop.ticket.issued', 'Billet bridge emis apres paiement shop', [
            'order_id' => $order->id,
            'link_id' => $link->id,
            'ticket_type_id' => $ticketType->id,
        ]);
        CatminEventBus::dispatch('bridge.event_shop.ticket.issued', [
            'order_id' => $order->id,
            'link_id' => $link->id,
            'ticket_type_id' => $ticketType->id,
        ]);
    }

    public function remainingCapacity(EventShopTicketType $ticketType): int
    {
        $ticketType->loadMissing('event');

        $issuedForType = EventShopOrderLink::query()
            ->where('ticket_type_id', $ticketType->id)
            ->where('status', 'issued')
            ->count();

        $allocationRemaining = $ticketType->allocation !== null
            ? max(0, (int) $ticketType->allocation - $issuedForType)
            : PHP_INT_MAX;

        $eventRemaining = $ticketType->event && $ticketType->event->capacity !== null
            ? max(0, (int) $ticketType->event->capacity - $ticketType->event->participants()->where('status', 'confirmed')->count())
            : PHP_INT_MAX;

        return (int) min($allocationRemaining, $eventRemaining);
    }

    private function log(string $action, string $message, array $context = []): void
    {
        if (!$this->loggerAvailable) {
            return;
        }

        try {
            app(SystemLogService::class)->log('info', $message, array_merge(['action' => $action], $context));
        } catch (\Throwable) {
        }
    }
}