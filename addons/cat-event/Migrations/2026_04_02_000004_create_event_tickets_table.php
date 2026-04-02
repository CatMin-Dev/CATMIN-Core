<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('event_tickets')) {
            Schema::create('event_tickets', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
                $table->foreignId('event_participant_id')->constrained('event_participants')->cascadeOnDelete();
                $table->string('ticket_number', 60)->unique();
                $table->string('qr_code', 500)->nullable();
                $table->string('status', 30)->default('active');
                $table->datetime('checkin_at')->nullable();
                $table->datetime('issued_at')->nullable();
                $table->timestamps();

                $table->index(['event_id', 'status']);
                $table->index('ticket_number');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('event_tickets');
    }
};
