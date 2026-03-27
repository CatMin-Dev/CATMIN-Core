<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('webhooks')) {
            Schema::create('webhooks', function (Blueprint $table): void {
                $table->id();
                $table->string('name', 191);
                $table->string('url', 500);
                $table->json('events')->nullable()->comment('Array of event names to listen to');
                $table->string('secret', 255)->nullable()->comment('HMAC-SHA256 signing secret');
                $table->string('status', 20)->default('active')->comment('active|inactive');
                $table->timestamp('last_triggered_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('webhooks');
    }
};
