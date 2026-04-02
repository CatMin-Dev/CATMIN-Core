<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('event_checkins')) {
            Schema::create('event_checkins', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
                $table->foreignId('event_ticket_id')->constrained('event_tickets')->cascadeOnDelete();
                $table->foreignId('event_participant_id')->constrained('event_participants')->cascadeOnDelete();
                $table->datetime('checkin_at');
                $table->string('checkin_method', 30)->default('manual');
                $table->unsignedBigInteger('admin_user_id')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['event_id', 'checkin_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('event_checkins');
    }
};
