<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('admin_notifications')) {
            return;
        }

        Schema::create('admin_notifications', function (Blueprint $table): void {
            $table->id();
            $table->string('type', 20)->default('info')->comment('info, warning, critical, success');
            $table->string('source', 60)->nullable()->comment('queue, webhooks, security, monitoring, mailer, system, etc.');
            $table->string('title', 255);
            $table->text('message');
            $table->string('action_url', 512)->nullable();
            $table->string('action_label', 80)->nullable();
            $table->string('dedupe_key', 128)->nullable()->comment('Fingerprint to prevent duplicate notifications');
            $table->boolean('is_read')->default(false)->index();
            $table->boolean('is_acknowledged')->default(false)->index();
            $table->unsignedBigInteger('user_id')->nullable()->index()->comment('Null = broadcast to all admins');
            $table->json('context')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamps();

            $table->index(['type', 'is_read', 'created_at']);
            $table->index(['source', 'created_at']);
            $table->index(['dedupe_key', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_notifications');
    }
};
