<?php

namespace Tests\Feature;

use App\Services\AddonLoader;
use App\Services\AddonManager;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use Addons\CatEvent\Services\EventAdminService;
use Addons\CatEvent\Models\Event;
use Addons\CatEvent\Models\EventParticipant;
use Addons\CatEvent\Models\EventTicket;

class EventAddonTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Run core migrations if needed, then addon-specific migrations
        $this->artisan('migrate', ['--force' => true]);

        if (!Schema::hasTable('events')) {
            $this->artisan('migrate', [
                '--force' => true,
                '--path'  => 'addons/cat-event/Migrations',
            ]);
        }
    }

    // ─── Addon discovery ──────────────────────────────────────────────────────

    public function test_cat_event_is_discovered_as_addon(): void
    {
        AddonManager::clearCache();

        $this->assertTrue(AddonManager::exists('cat-event'));

        $addon = AddonManager::find('cat-event');

        $this->assertNotNull($addon);
        $this->assertTrue((bool) ($addon->enabled ?? false));
    }

    public function test_event_routes_are_loaded_from_addon_namespace(): void
    {
        AddonManager::clearCache();
        AddonLoader::registerRoutes(app('router'));

        $route = Route::getRoutes()->getByName('admin.events.index');

        $this->assertNotNull($route);

        $action = (string) ($route?->getActionName() ?? '');

        $this->assertStringContainsString('Addons\\CatEvent\\Controllers\\Admin\\EventController@index', $action);
    }

    // ─── Classes existance ────────────────────────────────────────────────────

    public function test_event_service_and_models_exist(): void
    {
        $this->assertTrue(class_exists(EventAdminService::class));
        $this->assertTrue(class_exists(Event::class));
        $this->assertTrue(class_exists(EventParticipant::class));
        $this->assertTrue(class_exists(EventTicket::class));
        $this->assertTrue(class_exists(\Addons\CatEvent\Models\EventSession::class));
        $this->assertTrue(class_exists(\Addons\CatEvent\Models\EventCheckin::class));
    }

    // ─── Service CRUD ─────────────────────────────────────────────────────────

    public function test_event_crud_via_service(): void
    {
        $service = app(EventAdminService::class);

        $event = $service->create([
            'title'    => 'Conférence Test ' . uniqid(),
            'slug'     => '',
            'start_at' => now()->addDays(10)->format('Y-m-d H:i:s'),
            'end_at'   => now()->addDays(10)->addHours(3)->format('Y-m-d H:i:s'),
            'status'   => 'draft',
            'is_free'  => true,
        ]);

        $this->assertInstanceOf(Event::class, $event);
        $this->assertDatabaseHas('events', ['id' => $event->id]);

        $updated = $service->update($event, [
            'title'    => 'Conférence Test Modifiée',
            'slug'     => '',
            'start_at' => $event->start_at->format('Y-m-d H:i:s'),
            'end_at'   => $event->end_at->format('Y-m-d H:i:s'),
            'status'   => 'published',
            'is_free'  => true,
        ]);

        $this->assertEquals('published', $updated->status);

        $service->delete($updated);

        $this->assertDatabaseMissing('events', ['id' => $updated->id]);
    }

    // ─── Participant registration + ticket generation ─────────────────────────

    public function test_participant_registration_generates_ticket(): void
    {
        $service = app(EventAdminService::class);

        $event = $service->create([
            'title'    => 'Event Inscription ' . uniqid(),
            'slug'     => '',
            'start_at' => now()->addDays(5)->format('Y-m-d H:i:s'),
            'end_at'   => now()->addDays(5)->addHours(2)->format('Y-m-d H:i:s'),
            'status'   => 'published',
            'is_free'  => true,
        ]);

        $participant = $service->registerParticipant($event, [
            'first_name' => 'Jean',
            'last_name'  => 'Dupont',
            'email'      => 'jean.dupont.' . uniqid() . '@example.com',
        ]);

        $this->assertInstanceOf(EventParticipant::class, $participant);
        $this->assertEquals('confirmed', $participant->status);
        $this->assertDatabaseHas('event_participants', ['id' => $participant->id]);

        $ticket = EventTicket::query()->where('event_participant_id', $participant->id)->first();

        $this->assertNotNull($ticket);
        $this->assertEquals('active', $ticket->status);
        $this->assertStringStartsWith('EVT-', (string) $ticket->ticket_number);
    }

    // ─── Check-in ─────────────────────────────────────────────────────────────

    public function test_checkin_marks_ticket_as_used(): void
    {
        $service = app(EventAdminService::class);

        $event = $service->create([
            'title'    => 'Event Checkin ' . uniqid(),
            'slug'     => '',
            'start_at' => now()->addDays(1)->format('Y-m-d H:i:s'),
            'end_at'   => now()->addDays(1)->addHours(4)->format('Y-m-d H:i:s'),
            'status'   => 'published',
            'is_free'  => true,
        ]);

        $participant = $service->registerParticipant($event, [
            'first_name' => 'Alice',
            'email'      => 'alice.' . uniqid() . '@example.com',
        ]);

        /** @var EventTicket $ticket */
        $ticket  = EventTicket::query()->where('event_participant_id', $participant->id)->first();
        $checkin = $service->checkin($ticket, 'manual');

        $this->assertDatabaseHas('event_checkins', ['id' => $checkin->id]);

        $ticket->refresh();
        $this->assertEquals('used', $ticket->status);
        $this->assertNotNull($ticket->checkin_at);
    }

    public function test_double_checkin_throws_exception(): void
    {
        $service = app(EventAdminService::class);

        $event = $service->create([
            'title'    => 'Event Double Checkin ' . uniqid(),
            'slug'     => '',
            'start_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
            'end_at'   => now()->addDays(2)->addHours(2)->format('Y-m-d H:i:s'),
            'status'   => 'published',
            'is_free'  => true,
        ]);

        $participant = $service->registerParticipant($event, [
            'first_name' => 'Bob',
            'email'      => 'bob.' . uniqid() . '@example.com',
        ]);

        /** @var EventTicket $ticket */
        $ticket = EventTicket::query()->where('event_participant_id', $participant->id)->first();

        $service->checkin($ticket, 'manual');

        $this->expectException(\RuntimeException::class);
        $service->checkin($ticket->fresh(), 'manual');
    }

    // ─── Toggle status ────────────────────────────────────────────────────────

    public function test_toggle_status_publishes_and_unpublishes(): void
    {
        $service = app(EventAdminService::class);

        $event = $service->create([
            'title'    => 'Event Toggle ' . uniqid(),
            'slug'     => '',
            'start_at' => now()->addDays(3)->format('Y-m-d H:i:s'),
            'end_at'   => now()->addDays(3)->addHours(1)->format('Y-m-d H:i:s'),
            'status'   => 'draft',
            'is_free'  => true,
        ]);

        $this->assertEquals('draft', $event->status);

        $published = $service->toggleStatus($event);
        $this->assertEquals('published', $published->status);
        $this->assertNotNull($published->published_at);

        $draft = $service->toggleStatus($published);
        $this->assertEquals('draft', $draft->status);
    }
}
