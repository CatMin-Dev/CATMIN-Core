<?php

declare(strict_types=1);

namespace Tests\Unit\Event;

use Addons\CatEvent\Models\Event;
use Addons\CatEvent\Models\EventParticipant;
use Addons\CatEvent\Models\EventTicket;
use Addons\CatEvent\Services\EventCheckinService;
use Addons\CatEvent\Services\EventQrCodeService;
use Addons\CatEvent\Services\EventTicketService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EventTicketAndCheckinServiceTest extends TestCase
{
    protected EventTicketService $ticketService;
    protected EventCheckinService $checkinService;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', ':memory:');

        if (!Schema::hasTable('events')) {
            Schema::create('events', function (Blueprint $table): void {
                $table->id();
                $table->string('title');
                $table->string('slug');
                $table->string('status')->default('published');
                $table->timestamp('start_at')->nullable();
                $table->timestamp('end_at')->nullable();
                $table->timestamp('published_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('event_participants')) {
            Schema::create('event_participants', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('event_id');
                $table->unsignedBigInteger('event_session_id')->nullable();
                $table->string('first_name');
                $table->string('last_name')->nullable();
                $table->string('email');
                $table->string('phone')->nullable();
                $table->string('status')->default('confirmed');
                $table->text('notes')->nullable();
                $table->timestamp('registered_at')->nullable();
                $table->timestamp('confirmed_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('event_tickets')) {
            Schema::create('event_tickets', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('event_id');
                $table->unsignedBigInteger('event_participant_id');
                $table->unsignedBigInteger('participant_id')->nullable();
                $table->string('source', 20)->default('manual');
                $table->string('ticket_number', 60)->unique();
                $table->string('code', 80)->unique();
                $table->string('token', 120)->nullable()->unique();
                $table->string('qr_code', 5000)->nullable();
                $table->text('qr_payload')->nullable();
                $table->string('status', 30)->default('issued');
                $table->dateTime('checkin_at')->nullable();
                $table->dateTime('issued_at')->nullable();
                $table->dateTime('used_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('event_checkins')) {
            Schema::create('event_checkins', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('event_id');
                $table->unsignedBigInteger('event_ticket_id');
                $table->unsignedBigInteger('ticket_id')->nullable();
                $table->unsignedBigInteger('event_participant_id');
                $table->unsignedBigInteger('checked_in_by')->nullable();
                $table->dateTime('checked_in_at')->nullable();
                $table->string('location', 120)->nullable();
                $table->dateTime('checkin_at');
                $table->string('checkin_method', 30)->default('manual');
                $table->unsignedBigInteger('admin_user_id')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        $this->ticketService = new EventTicketService(new EventQrCodeService());
        $this->checkinService = new EventCheckinService();
    }

    public function test_ticket_is_generated_with_unique_code_and_token(): void
    {
        [$event, $participant] = $this->makeEventAndParticipant();

        $ticket = $this->ticketService->issue($event, $participant, 'manual');

        $this->assertNotNull($ticket->id);
        $this->assertSame('issued', $ticket->status);
        $this->assertNotEmpty($ticket->code);
        $this->assertNotEmpty($ticket->token);
        $this->assertSame('manual', $ticket->source);
    }

    public function test_qr_payload_and_svg_are_created(): void
    {
        [$event, $participant] = $this->makeEventAndParticipant();

        $ticket = $this->ticketService->issue($event, $participant, 'manual');

        $this->assertNotEmpty($ticket->qr_payload);
        $this->assertNotEmpty($ticket->qr_code);
        $this->assertStringStartsWith('data:image/svg+xml;base64,', (string) $ticket->qr_code);
    }

    public function test_valid_checkin_marks_ticket_as_used(): void
    {
        [$event, $participant] = $this->makeEventAndParticipant();
        $ticket = $this->ticketService->issue($event, $participant, 'manual');

        $checkin = $this->checkinService->checkinByCode($event, (string) $ticket->code, 'qr', 1, 'Gate A', null);

        $ticket->refresh();

        $this->assertNotNull($checkin->id);
        $this->assertSame('used', $ticket->status);
        $this->assertNotNull($ticket->used_at);
    }

    public function test_double_checkin_is_refused(): void
    {
        [$event, $participant] = $this->makeEventAndParticipant();
        $ticket = $this->ticketService->issue($event, $participant, 'manual');

        $this->checkinService->checkinByCode($event, (string) $ticket->code, 'manual', 1, null, null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('déjà');

        $this->checkinService->checkinByCode($event, (string) $ticket->code, 'manual', 1, null, null);
    }

    public function test_cancelled_ticket_is_refused(): void
    {
        [$event, $participant] = $this->makeEventAndParticipant();
        $ticket = $this->ticketService->issue($event, $participant, 'manual');
        $ticket->update(['status' => 'cancelled']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('annulé');

        $this->checkinService->checkinByCode($event, (string) $ticket->code, 'manual', 1, null, null);
    }

    public function test_attendance_search_finds_by_name_email_and_code(): void
    {
        [$event, $participant] = $this->makeEventAndParticipant('Alice', 'Doe', 'alice@example.test');
        $ticket = $this->ticketService->issue($event, $participant, 'manual');
        $this->checkinService->checkinByCode($event, (string) $ticket->code, 'manual', 1, null, null);

        $byName = $this->checkinService->attendanceListing($event, ['q' => 'Alice']);
        $byEmail = $this->checkinService->attendanceListing($event, ['q' => 'alice@example.test']);
        $byCode = $this->checkinService->attendanceListing($event, ['q' => (string) $ticket->code]);

        $this->assertSame(1, $byName->total());
        $this->assertSame(1, $byEmail->total());
        $this->assertSame(1, $byCode->total());
    }

    /**
     * @return array{Event,EventParticipant}
     */
    private function makeEventAndParticipant(
        string $firstName = 'John',
        ?string $lastName = 'Doe',
        string $email = 'john@example.test'
    ): array {
        $event = Event::query()->create([
            'title' => 'Event X',
            'slug' => 'event-x',
            'status' => 'published',
            'start_at' => now()->subHour(),
            'end_at' => now()->addHour(),
            'published_at' => now()->subDay(),
        ]);

        $participant = EventParticipant::query()->create([
            'event_id' => $event->id,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'status' => 'confirmed',
            'registered_at' => now()->subHour(),
            'confirmed_at' => now()->subHour(),
        ]);

        return [$event, $participant];
    }
}
