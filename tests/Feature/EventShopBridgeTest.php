<?php

namespace Tests\Feature;

use App\Services\AddonManager;
use App\Services\CatminEventBus;
use App\Services\CatminHookLoader;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use Addons\CatEvent\Models\Event;
use Addons\CatEvent\Models\EventParticipant;
use Addons\CatEvent\Models\EventTicket;
use Addons\CatEvent\Services\EventAdminService;
use Addons\CatminShop\Models\Product;
use Addons\CatminShop\Services\ShopAdminService;
use Addons\CatminEventShopBridge\Models\EventShopOrderLink;
use Addons\CatminEventShopBridge\Models\EventShopTicketType;
use Addons\CatminEventShopBridge\Services\EventShopBridgeService;

class EventShopBridgeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate', ['--force' => true]);

        if (!Schema::hasTable('events')) {
            $this->artisan('migrate', ['--force' => true, '--path' => 'addons/cat-event/Migrations']);
        }

        if (!Schema::hasTable('shop_products')) {
            $this->artisan('migrate', ['--force' => true, '--path' => 'addons/catmin-shop/Migrations']);
        }

        if (!Schema::hasTable('event_shop_bridge_ticket_types')) {
            $this->artisan('migrate', ['--force' => true, '--path' => 'addons/catmin-event-shop-bridge/Migrations']);
        }

        AddonManager::clearCache();
        CatminHookLoader::load();
    }

    public function test_ticket_product_is_created_for_event_ticket_type(): void
    {
        $bridge = app(EventShopBridgeService::class);
        $event = $this->makeEvent();

        $ticketType = $bridge->createTicketType([
            'event_id' => $event->id,
            'name' => 'VIP',
            'price' => 49.90,
            'allocation' => 15,
            'status' => 'active',
            'description' => 'Acces VIP',
            'auto_cancel_on_order_cancel' => true,
        ]);

        $this->assertInstanceOf(EventShopTicketType::class, $ticketType);
        $this->assertNotNull($ticketType->shop_product_id);
        $this->assertDatabaseHas('event_shop_bridge_ticket_types', ['id' => $ticketType->id]);
        $this->assertDatabaseHas('shop_products', ['id' => $ticketType->shop_product_id, 'sku' => $ticketType->sku]);
    }

    public function test_paid_order_event_generates_ticket(): void
    {
        $shop = app(ShopAdminService::class);
        $ticketType = $this->makeTicketType(5);

        $order = $shop->createOrder([
            'customer_name' => 'Jean Dupont',
            'customer_email' => 'jean.' . uniqid() . '@example.com',
            'status' => 'pending',
            'currency' => 'EUR',
            'tax_total' => 0,
            'shipping_total' => 0,
            'items' => [
                ['product_id' => $ticketType->shop_product_id, 'quantity' => 1],
            ],
        ]);

        CatminEventBus::dispatch('shop.order.paid', ['order_id' => $order->id, 'order_number' => $order->order_number]);

        $this->assertSame(1, EventParticipant::query()->where('event_id', $ticketType->event_id)->count());
        $this->assertSame(1, EventTicket::query()->where('event_id', $ticketType->event_id)->count());
        $this->assertDatabaseHas('event_shop_bridge_order_links', [
            'shop_order_id' => $order->id,
            'ticket_type_id' => $ticketType->id,
            'status' => 'issued',
        ]);
    }

    public function test_paid_order_event_is_idempotent(): void
    {
        $shop = app(ShopAdminService::class);
        $ticketType = $this->makeTicketType(5);

        $order = $shop->createOrder([
            'customer_name' => 'Alice Example',
            'customer_email' => 'alice.' . uniqid() . '@example.com',
            'status' => 'pending',
            'currency' => 'EUR',
            'tax_total' => 0,
            'shipping_total' => 0,
            'items' => [
                ['product_id' => $ticketType->shop_product_id, 'quantity' => 1],
            ],
        ]);

        CatminEventBus::dispatch('shop.order.paid', ['order_id' => $order->id, 'order_number' => $order->order_number]);
        CatminEventBus::dispatch('shop.order.paid', ['order_id' => $order->id, 'order_number' => $order->order_number]);

        $this->assertSame(1, EventParticipant::query()->where('event_id', $ticketType->event_id)->count());
        $this->assertSame(1, EventTicket::query()->where('event_id', $ticketType->event_id)->count());
        $this->assertSame(1, EventShopOrderLink::query()->where('shop_order_id', $order->id)->count());
    }

    public function test_order_cancellation_cancels_issued_ticket(): void
    {
        $shop = app(ShopAdminService::class);
        $ticketType = $this->makeTicketType(5);

        $order = $shop->createOrder([
            'customer_name' => 'Bob Martin',
            'customer_email' => 'bob.' . uniqid() . '@example.com',
            'status' => 'paid',
            'currency' => 'EUR',
            'tax_total' => 0,
            'shipping_total' => 0,
            'items' => [
                ['product_id' => $ticketType->shop_product_id, 'quantity' => 1],
            ],
        ]);

        $link = EventShopOrderLink::query()->where('shop_order_id', $order->id)->firstOrFail();
        $ticket = EventTicket::query()->findOrFail($link->event_ticket_id);

        $this->assertSame('issued', $link->status);
        $this->assertSame('active', $ticket->status);

        $shop->transitionOrder($order->fresh(), 'cancelled');

        $link->refresh();
        $ticket->refresh();

        $this->assertSame('cancelled', $link->status);
        $this->assertSame('cancelled', $ticket->status);
    }

    public function test_capacity_exhaustion_prevents_second_ticket_issue(): void
    {
        $shop = app(ShopAdminService::class);
        $ticketType = $this->makeTicketType(1);

        $firstOrder = $shop->createOrder([
            'customer_name' => 'First User',
            'customer_email' => 'first.' . uniqid() . '@example.com',
            'status' => 'paid',
            'currency' => 'EUR',
            'tax_total' => 0,
            'shipping_total' => 0,
            'items' => [
                ['product_id' => $ticketType->shop_product_id, 'quantity' => 1],
            ],
        ]);

        $secondOrder = $shop->createOrder([
            'customer_name' => 'Second User',
            'customer_email' => 'second.' . uniqid() . '@example.com',
            'status' => 'paid',
            'currency' => 'EUR',
            'tax_total' => 0,
            'shipping_total' => 0,
            'items' => [
                ['product_id' => $ticketType->shop_product_id, 'quantity' => 1],
            ],
        ]);

        $this->assertSame(1, EventTicket::query()->where('event_id', $ticketType->event_id)->count());
        $this->assertDatabaseHas('event_shop_bridge_order_links', [
            'shop_order_id' => $firstOrder->id,
            'status' => 'issued',
        ]);
        $this->assertDatabaseHas('event_shop_bridge_order_links', [
            'shop_order_id' => $secondOrder->id,
            'status' => 'failed_capacity',
        ]);

        $product = Product::query()->findOrFail($ticketType->shop_product_id);
        $this->assertSame(0, (int) $product->stock_quantity);
    }

    private function makeEvent(): Event
    {
        return app(EventAdminService::class)->create([
            'title' => 'Bridge Event ' . uniqid(),
            'slug' => '',
            'start_at' => now()->addDays(7)->format('Y-m-d H:i:s'),
            'end_at' => now()->addDays(7)->addHours(2)->format('Y-m-d H:i:s'),
            'status' => 'published',
            'is_free' => false,
            'ticket_price' => 10,
            'capacity' => 50,
        ]);
    }

    private function makeTicketType(int $allocation): EventShopTicketType
    {
        $event = $this->makeEvent();

        return app(EventShopBridgeService::class)->createTicketType([
            'event_id' => $event->id,
            'name' => 'Bridge Ticket ' . uniqid(),
            'price' => 19.90,
            'allocation' => $allocation,
            'status' => 'active',
            'description' => 'Billet bridge',
            'auto_cancel_on_order_cancel' => true,
        ]);
    }
}