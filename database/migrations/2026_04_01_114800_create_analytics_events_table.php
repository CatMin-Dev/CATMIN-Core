<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('analytics_events', function (Blueprint $table): void {
            $table->id();
            $table->string('event_name', 120);
            $table->string('domain', 64)->index();
            $table->string('action', 64)->index();
            $table->string('status', 20)->default('success')->index();
            $table->string('actor_type', 30)->nullable();
            $table->unsignedBigInteger('actor_id')->nullable()->index();
            $table->string('role', 80)->nullable()->index();
            $table->string('route_name', 120)->nullable()->index();
            $table->string('path', 190)->nullable();
            $table->json('context')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->timestamps();

            $table->index(['event_name', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_events');
    }
};
