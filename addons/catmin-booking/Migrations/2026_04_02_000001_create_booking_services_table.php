<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('booking_services')) {
            return;
        }

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

            $table->index('is_active');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_services');
    }
};
