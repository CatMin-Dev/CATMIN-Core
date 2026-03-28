<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('admin_password_reset_tokens')) {
            Schema::create('admin_password_reset_tokens', function (Blueprint $table): void {
                $table->string('email')->primary();
                $table->string('token', 128);
                $table->timestamp('created_at')->nullable();
                $table->timestamp('used_at')->nullable();
                $table->string('requested_ip', 45)->nullable();
                $table->string('used_ip', 45)->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_password_reset_tokens');
    }
};
