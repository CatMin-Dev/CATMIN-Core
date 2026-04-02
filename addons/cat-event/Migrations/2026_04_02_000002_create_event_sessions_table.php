<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('event_sessions')) {
            Schema::create('event_sessions', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
                $table->string('title', 255);
                $table->datetime('start_at');
                $table->datetime('end_at');
                $table->string('location', 255)->nullable();
                $table->unsignedInteger('capacity')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index('event_id');
                $table->index('start_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('event_sessions');
    }
};
