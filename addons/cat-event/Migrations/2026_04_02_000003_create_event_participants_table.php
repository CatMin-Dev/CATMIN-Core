<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('event_participants')) {
            Schema::create('event_participants', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
                $table->foreignId('event_session_id')->nullable()->constrained('event_sessions')->nullOnDelete();
                $table->string('first_name', 120);
                $table->string('last_name', 120)->nullable();
                $table->string('email', 191);
                $table->string('phone', 50)->nullable();
                $table->string('status', 30)->default('pending');
                $table->text('notes')->nullable();
                $table->datetime('registered_at')->nullable();
                $table->datetime('confirmed_at')->nullable();
                $table->timestamps();

                $table->index(['event_id', 'status']);
                $table->index('email');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('event_participants');
    }
};
