<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('system_logs')) {
            return;
        }

        Schema::create('system_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('channel', 32)->default('application');
            $table->string('level', 32)->default('info');
            $table->string('event', 128)->default('event');
            $table->text('message');
            $table->json('context')->nullable();
            $table->string('admin_username', 191)->nullable();
            $table->string('method', 16)->nullable();
            $table->text('url')->nullable();
            $table->string('ip_address', 64)->nullable();
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->timestamps();

            $table->index(['channel', 'level']);
            $table->index('event');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_logs');
    }
};
