<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('webhooks', function (Blueprint $table) {
            // Anti-replay and idempotence tracking
            $table->boolean('anti_replay_enabled')->default(true)->after('secret')->comment('Enable anti-replay protection');
            $table->string('rotation_status')->default('current')->after('anti_replay_enabled')->comment('current, pending, deprecated');
            $table->string('pending_secret')->nullable()->after('rotation_status')->comment('Secret being rotated in');
            $table->timestamp('pending_rotation_at')->nullable()->after('pending_secret')->comment('When to activate pending secret');
        });

        // Create nonce storage table for anti-replay
        Schema::create('webhook_nonces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('webhook_id')->constrained('webhooks')->onDelete('cascade');
            $table->string('nonce', 255)->unique();
            $table->timestamp('expires_at')->index();
            $table->timestamps();

            $table->index('expires_at');
        });

        // Create event tracking table for idempotence
        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('webhook_id')->constrained('webhooks')->onDelete('cascade');
            $table->string('event_id', 255)->unique()->index();
            $table->string('event_type', 255)->index();
            $table->timestamp('received_at');
            $table->json('payload')->nullable();
            $table->string('status')->default('processed')->comment('processed, failed, skipped');
            $table->timestamps();

            $table->index(['webhook_id', 'created_at']);
        });

        // Create delivery attempts table for outgoing webhook logging
        Schema::create('webhook_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('webhook_id')->constrained('webhooks')->onDelete('cascade');
            $table->string('event_type', 255)->index();
            $table->json('payload');
            $table->string('status')->default('pending')->comment('pending, sending, success, failed, retrying');
            $table->integer('attempt_number')->default(1);
            $table->integer('max_attempts')->default(5);
            $table->timestamp('next_retry_at')->nullable()->index();
            $table->string('response_code')->nullable();
            $table->text('response_body')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['webhook_id', 'status', 'created_at']);
            $table->index(['next_retry_at', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_deliveries');
        Schema::dropIfExists('webhook_events');
        Schema::dropIfExists('webhook_nonces');
        
        Schema::table('webhooks', function (Blueprint $table) {
            $table->dropColumn([
                'anti_replay_enabled',
                'rotation_status',
                'pending_secret',
                'pending_rotation_at',
            ]);
        });
    }
};
