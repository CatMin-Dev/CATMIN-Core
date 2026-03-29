<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('monitoring_snapshots')) {
            Schema::create('monitoring_snapshots', function (Blueprint $table): void {
                $table->id();
                $table->string('global_status', 20)->index();
                $table->unsignedSmallInteger('score')->default(100);
                $table->json('checks_json')->nullable();
                $table->unsignedInteger('incidents_open')->default(0);
                $table->unsignedInteger('incidents_critical')->default(0);
                $table->timestamps();

                $table->index('created_at');
            });
        }

        if (!Schema::hasTable('monitoring_incidents')) {
            Schema::create('monitoring_incidents', function (Blueprint $table): void {
                $table->id();
                $table->string('fingerprint', 64)->unique();
                $table->string('domain', 80)->index();
                $table->string('severity', 20)->default('warning')->index();
                $table->string('status', 20)->default('warning')->index();
                $table->string('title', 255);
                $table->text('message')->nullable();
                $table->unsignedInteger('occurrences')->default(1);
                $table->timestamp('first_seen_at')->nullable();
                $table->timestamp('last_seen_at')->nullable()->index();
                $table->timestamp('recovered_at')->nullable()->index();
                $table->json('context')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('monitoring_incidents');
        Schema::dropIfExists('monitoring_snapshots');
    }
};
