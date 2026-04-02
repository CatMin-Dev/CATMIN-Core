<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('events')) {
            Schema::create('events', function (Blueprint $table): void {
                $table->id();
                $table->string('title', 255);
                $table->string('slug', 255)->unique();
                $table->text('description')->nullable();
                $table->string('location', 255)->nullable();
                $table->text('address')->nullable();
                $table->datetime('start_at');
                $table->datetime('end_at');
                $table->unsignedInteger('capacity')->nullable();
                $table->string('status', 30)->default('draft');
                $table->string('featured_image', 500)->nullable();
                $table->string('organizer_name', 191)->nullable();
                $table->string('organizer_email', 191)->nullable();
                $table->boolean('is_free')->default(true);
                $table->decimal('ticket_price', 12, 2)->default(0);
                $table->boolean('registration_enabled')->default(true);
                $table->datetime('registration_deadline')->nullable();
                $table->datetime('published_at')->nullable();
                $table->timestamps();

                $table->index(['status', 'start_at']);
                $table->index('start_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
