<?php

declare(strict_types=1);

namespace Tests\Unit\Booking;

use Addons\CatminBooking\Models\Booking;
use Addons\CatminBooking\Models\BookingService;
use Addons\CatminBooking\Models\BookingSlot;
use Addons\CatminBooking\Services\AvailabilityEngine;
use Addons\CatminBooking\Services\BookingCalendarService;
use Addons\CatminBooking\Services\BookingPolicyService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AvailabilityEngineTest extends TestCase
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

    public function test_availability_is_computed_correctly(): void
    {
        [$slot] = $this->seedServiceAndSlot(3, 1, 'open', false);

        $engine = new AvailabilityEngine(new BookingPolicyService());
        $availability = $engine->slotAvailability($slot);

        $this->assertTrue($availability['bookable']);
        $this->assertSame(2, $availability['remaining_capacity']);
    }

    public function test_full_slot_blocks_next_booking_when_overbooking_disabled(): void
    {
        [$slot] = $this->seedServiceAndSlot(1, 1, 'open', false);

        $engine = new AvailabilityEngine(new BookingPolicyService());

        $this->assertFalse($engine->canConfirm($slot, 'confirmed'));
    }

    public function test_cancellation_frees_capacity(): void
    {
        [$slot, $booking] = $this->seedServiceAndSlot(1, 1, 'open', false);

        $policy = new BookingPolicyService();

        $previousConsumes = $policy->consumesCapacity((string) $booking->status);
        $nextConsumes = $policy->consumesCapacity('cancelled');

        if ($previousConsumes && !$nextConsumes) {
            $slot->update(['booked_count' => max(0, ((int) $slot->booked_count) - 1)]);
        }

        $slot->refresh();
        $this->assertSame(0, (int) $slot->booked_count);
    }

    public function test_closed_slot_is_not_bookable(): void
    {
        [$slot] = $this->seedServiceAndSlot(5, 0, 'closed', false);

        $engine = new AvailabilityEngine(new BookingPolicyService());

        $this->assertFalse($engine->slotAvailability($slot)['bookable']);
    }

    public function test_calendar_service_returns_expected_slots(): void
    {
        [$slot] = $this->seedServiceAndSlot(5, 1, 'open', false);

        $calendar = new BookingCalendarService(new AvailabilityEngine(new BookingPolicyService()));
        $result = $calendar->range(now()->subHour()->toDateTimeString(), now()->addDays(2)->toDateTimeString());

        $this->assertIsArray($result);
        $this->assertArrayHasKey('slots', $result);
        $this->assertNotEmpty($result['slots']);
        $this->assertSame($slot->id, $result['slots'][0]['id']);
    }

    /**
     * @return array{0: BookingSlot, 1: Booking}
     */
    private function seedServiceAndSlot(int $capacity, int $bookedCount, string $slotStatus, bool $allowOverbooking): array
    {
        $service = BookingService::query()->create([
            'name' => 'Consultation',
            'slug' => 'consultation-' . uniqid(),
            'duration_minutes' => 30,
            'buffer_before_minutes' => 5,
            'buffer_after_minutes' => 5,
            'price_cents' => 1000,
            'is_active' => true,
        ]);

        $slot = BookingSlot::query()->create([
            'booking_service_id' => $service->id,
            'start_at' => now()->addDay(),
            'end_at' => now()->addDay()->addMinutes(30),
            'capacity' => $capacity,
            'booked_count' => $bookedCount,
            'status' => $slotStatus,
            'allow_overbooking' => $allowOverbooking,
            'is_active' => true,
        ]);

        $booking = Booking::query()->create([
            'booking_service_id' => $service->id,
            'booking_slot_id' => $slot->id,
            'status' => $bookedCount > 0 ? 'confirmed' : 'pending',
            'customer_name' => 'Client Test',
            'customer_email' => 'client@example.test',
            'confirmation_code' => 'BK-' . uniqid(),
        ]);

        return [$slot, $booking];
    }

    private function createTables(): void
    {
        Schema::dropAllTables();

        Schema::create('booking_services', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 191);
            $table->string('slug', 191)->unique();
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('duration_minutes')->default(30);
            $table->unsignedSmallInteger('buffer_before_minutes')->default(0);
            $table->unsignedSmallInteger('buffer_after_minutes')->default(0);
            $table->unsignedInteger('price_cents')->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('booking_slots', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('booking_service_id');
            $table->dateTime('start_at');
            $table->dateTime('end_at');
            $table->unsignedSmallInteger('capacity')->default(1);
            $table->unsignedSmallInteger('booked_count')->default(0);
            $table->string('status', 30)->default('open');
            $table->boolean('allow_overbooking')->default(false);
            $table->string('blocked_reason', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('bookings', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('booking_service_id');
            $table->unsignedBigInteger('booking_slot_id');
            $table->string('status', 32)->default('pending');
            $table->string('customer_name', 191);
            $table->string('customer_email', 191);
            $table->string('customer_phone', 64)->nullable();
            $table->text('notes')->nullable();
            $table->text('internal_note')->nullable();
            $table->string('confirmation_code', 64)->unique();
            $table->dateTime('confirmed_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->timestamps();
        });
    }
}
