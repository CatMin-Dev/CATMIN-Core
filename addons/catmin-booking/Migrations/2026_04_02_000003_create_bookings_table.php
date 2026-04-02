<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bookings')) {
            return;
        }

        Schema::create('bookings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('booking_service_id')->constrained('booking_services')->cascadeOnDelete();
            $table->foreignId('booking_slot_id')->constrained('booking_slots')->cascadeOnDelete();
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

            $table->index(['status', 'created_at']);
            $table->index(['booking_service_id', 'booking_slot_id']);
            $table->index('customer_email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
