<?php

declare(strict_types=1);

namespace Tests\Unit\Event;

use Addons\CatEvent\Models\Event;
use Addons\CatEvent\Services\EventPublicFlowService;
use Addons\CatEvent\Services\EventRegistrationService;
use Addons\CatEvent\Services\EventTicketService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EventPublicFlowServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('pdo_sqlite extension is not available.');
        }

        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', ':memory:');

        app('db')->purge('sqlite');
        app('db')->reconnect('sqlite');

        $this->createTables();
    }

    public function test_public_by_slug_only_returns_published_event(): void
    {
        Event::query()->create([
            'title' => 'Draft Event',
            'slug' => 'draft-event',
            'start_at' => now()->addDay(),
            'end_at' => now()->addDay()->addHour(),
            'status' => 'draft',
            'published_at' => null,
            'registration_enabled' => true,
            'participation_mode' => 'free_registration',
        ]);

        $published = Event::query()->create([
            'title' => 'Published Event',
            'slug' => 'published-event',
            'start_at' => now()->addDay(),
            'end_at' => now()->addDay()->addHour(),
            'status' => 'published',
            'published_at' => now()->subMinute(),
            'registration_enabled' => true,
            'participation_mode' => 'free_registration',
        ]);

        $service = new EventPublicFlowService();

        $this->assertNull($service->publicBySlug('draft-event'));
        $this->assertSame($published->id, $service->publicBySlug('published-event')?->id);
    }

    public function test_simple_registration_creates_participant(): void
    {
        $event = Event::query()->create([
            'title' => 'Approval Event',
            'slug' => 'approval-event',
            'start_at' => now()->addDay(),
            'end_at' => now()->addDay()->addHour(),
            'status' => 'published',
            'published_at' => now()->subMinute(),
            'capacity' => 10,
            'registration_enabled' => true,
            'participation_mode' => 'approval_required',
            'allow_waitlist' => false,
            'max_places_per_registration' => 2,
        ]);

        $ticketService = $this->createMock(EventTicketService::class);
        $registrationService = new EventRegistrationService(new EventPublicFlowService(), $ticketService);

        $participant = $registrationService->register($event, [
            'name' => 'Alice Martin',
            'email' => 'alice@example.test',
            'phone' => '0600000000',
            'seats_count' => 2,
            'form_token' => 'token-approval',
        ]);

        $this->assertSame('pending', $participant->status);
        $this->assertSame(2, (int) $participant->seats_count);
        $this->assertDatabaseHas('event_participants', [
            'event_id' => $event->id,
            'email' => 'alice@example.test',
            'status' => 'pending',
        ]);
    }

    public function test_capacity_reached_blocks_registration_without_waitlist(): void
    {
        $event = Event::query()->create([
            'title' => 'Limited Event',
            'slug' => 'limited-event',
            'start_at' => now()->addDay(),
            'end_at' => now()->addDay()->addHour(),
            'status' => 'published',
            'published_at' => now()->subMinute(),
            'capacity' => 1,
            'registration_enabled' => true,
            'participation_mode' => 'free_registration',
            'allow_waitlist' => false,
            'max_places_per_registration' => 1,
        ]);

        \Addons\CatEvent\Models\EventParticipant::query()->create([
            'event_id' => $event->id,
            'first_name' => 'Bob',
            'email' => 'bob@example.test',
            'status' => 'confirmed',
            'seats_count' => 1,
        ]);

        $ticketService = $this->createMock(EventTicketService::class);
        $registrationService = new EventRegistrationService(new EventPublicFlowService(), $ticketService);

        $this->expectException(\RuntimeException::class);
        $registrationService->register($event, [
            'name' => 'Charlie Test',
            'email' => 'charlie@example.test',
            'form_token' => 'token-limited',
        ]);
    }

    public function test_ticket_required_mode_exposes_shop_cta_when_bridge_ticket_exists(): void
    {
        $event = Event::query()->create([
            'title' => 'Ticket Event',
            'slug' => 'ticket-event',
            'start_at' => now()->addDay(),
            'end_at' => now()->addDay()->addHour(),
            'status' => 'published',
            'published_at' => now()->subMinute(),
            'registration_enabled' => true,
            'participation_mode' => 'ticket_required',
        ]);

        Schema::create('event_shop_bridge_ticket_types', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('event_id');
            $table->unsignedBigInteger('shop_product_id')->nullable();
            $table->string('name');
            $table->string('slug');
            $table->string('sku');
            $table->decimal('price', 12, 2)->default(0);
            $table->string('status', 30)->default('active');
            $table->timestamps();
        });

        \Illuminate\Support\Facades\DB::table('event_shop_bridge_ticket_types')->insert([
            'event_id' => $event->id,
            'shop_product_id' => 99,
            'name' => 'Billet standard',
            'slug' => 'standard',
            'sku' => 'SKU-99',
            'price' => 20,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Config::set('cat_event.shop_redirect_pattern', '/shop/products/{product_id}');

        $state = (new EventPublicFlowService())->buildPublicState($event);

        $this->assertSame('shop', $state['cta']['action']);
        $this->assertStringContainsString('/shop/products/99', (string) $state['cta']['url']);
    }

    private function createTables(): void
    {
        Schema::dropAllTables();

        Schema::create('events', function (Blueprint $table): void {
            $table->id();
            $table->string('title', 255);
            $table->string('slug', 255)->unique();
            $table->text('description')->nullable();
            $table->string('location', 255)->nullable();
            $table->text('address')->nullable();
            $table->dateTime('start_at');
            $table->dateTime('end_at');
            $table->unsignedInteger('capacity')->nullable();
            $table->string('status', 30)->default('draft');
            $table->string('featured_image', 500)->nullable();
            $table->string('organizer_name', 191)->nullable();
            $table->string('organizer_email', 191)->nullable();
            $table->boolean('is_free')->default(true);
            $table->decimal('ticket_price', 12, 2)->default(0);
            $table->boolean('registration_enabled')->default(true);
            $table->string('participation_mode', 40)->default('free_registration');
            $table->string('external_url', 500)->nullable();
            $table->boolean('allow_waitlist')->default(false);
            $table->unsignedSmallInteger('max_places_per_registration')->default(1);
            $table->dateTime('registration_deadline')->nullable();
            $table->dateTime('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('event_participants', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('event_id');
            $table->unsignedBigInteger('event_session_id')->nullable();
            $table->string('first_name', 120);
            $table->string('last_name', 120)->nullable();
            $table->string('email', 191);
            $table->string('phone', 50)->nullable();
            $table->unsignedSmallInteger('seats_count')->default(1);
            $table->string('status', 30)->default('pending');
            $table->string('source', 40)->default('admin');
            $table->string('idempotency_key', 128)->nullable();
            $table->text('notes')->nullable();
            $table->dateTime('registered_at')->nullable();
            $table->dateTime('confirmed_at')->nullable();
            $table->timestamps();
        });
    }
}
