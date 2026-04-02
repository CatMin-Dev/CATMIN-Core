<?php

namespace Tests\Unit\Booking;

use Addons\CatminBooking\Models\BookingService;
use Addons\CatminBooking\Models\BookingSlot;
use Addons\CatminBooking\Services\BookingAdminService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BookingAdminServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('pdo_sqlite extension is not available.');
        }

        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', ':memory:');
        Config::set('booking.mail_notifications', false);

        app('db')->purge('sqlite');
        app('db')->reconnect('sqlite');

        $this->createTables();
    }

    public function test_create_booking_increments_booked_count(): void
    {
        $service = app(BookingAdminService::class);

        $bookingService = BookingService::query()->create([
            'name' => 'Consultation',
            'slug' => 'consultation',
            'duration_minutes' => 30,
            'price_cents' => 5000,
            'is_active' => true,
        ]);

        $slot = BookingSlot::query()->create([
            'booking_service_id' => $bookingService->id,
            'start_at' => now()->addDay(),
            'end_at' => now()->addDay()->addMinutes(30),
            'capacity' => 2,
            'booked_count' => 0,
            'is_active' => true,
        ]);

        $booking = $service->createBooking([
            'booking_slot_id' => $slot->id,
            'status' => 'pending',
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.test',
        ]);

        $this->assertSame('pending', $booking->status);
        $this->assertNotEmpty($booking->confirmation_code);

        $slot->refresh();
        $this->assertSame(1, (int) $slot->booked_count);
    }

    public function test_booking_is_rejected_when_slot_is_full(): void
    {
        $this->expectException(\RuntimeException::class);

        $service = app(BookingAdminService::class);

        $bookingService = BookingService::query()->create([
            'name' => 'Audit',
            'slug' => 'audit',
            'duration_minutes' => 60,
            'price_cents' => 10000,
            'is_active' => true,
        ]);

        $slot = BookingSlot::query()->create([
            'booking_service_id' => $bookingService->id,
            'start_at' => now()->addDay(),
            'end_at' => now()->addDay()->addHour(),
            'capacity' => 1,
            'booked_count' => 1,
            'is_active' => true,
        ]);

        $service->createBooking([
            'booking_slot_id' => $slot->id,
            'status' => 'pending',
            'customer_name' => 'Jane Doe',
            'customer_email' => 'jane@example.test',
        ]);
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
            $table->unsignedInteger('price_cents')->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('booking_slots', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('booking_service_id');
            $table->timestamp('start_at');
            $table->timestamp('end_at');
            $table->unsignedSmallInteger('capacity')->default(1);
            $table->unsignedSmallInteger('booked_count')->default(0);
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
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });
    }
}
