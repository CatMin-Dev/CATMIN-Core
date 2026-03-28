<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('admin_sessions')) {
            Schema::create('admin_sessions', function (Blueprint $table): void {
                $table->string('session_id', 128)->primary();
                $table->unsignedBigInteger('admin_user_id');
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamp('last_activity_at')->nullable();
                $table->timestamp('revoked_at')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();

                $table->index(['admin_user_id', 'revoked_at']);
                $table->index('last_activity_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_sessions');
    }
};
