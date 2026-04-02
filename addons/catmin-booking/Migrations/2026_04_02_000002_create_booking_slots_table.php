<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('booking_slots')) {
            return;
        }

        Schema::create('booking_slots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('booking_service_id')->constrained('booking_services')->cascadeOnDelete();
            $table->timestamp('start_at');
            $table->timestamp('end_at');
            $table->unsignedSmallInteger('capacity')->default(1);
            $table->unsignedSmallInteger('booked_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['booking_service_id', 'start_at']);
            $table->index(['is_active', 'start_at']);
            $table->unique(['booking_service_id', 'start_at', 'end_at'], 'booking_slots_unique_range');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_slots');
    }
};
