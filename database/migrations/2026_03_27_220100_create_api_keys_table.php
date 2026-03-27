<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('api_keys')) {
            return;
        }

        Schema::create('api_keys', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 191);
            $table->string('key_hash', 128)->unique();
            $table->json('scopes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->string('last_used_ip', 64)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'expires_at']);
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_keys');
    }
};
