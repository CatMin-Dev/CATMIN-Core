<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add fields for retention and archival to system_logs
        if (Schema::hasTable('system_logs')) {
            Schema::table('system_logs', function (Blueprint $table) {
                if (!Schema::hasColumn('system_logs', 'retention_policy')) {
                    $table->string('retention_policy')->nullable()->comment('daily, weekly, monthly, permanent');
                }
                if (!Schema::hasColumn('system_logs', 'archived_at')) {
                    $table->timestamp('archived_at')->nullable()->index()->comment('When this log was archived');
                }
                if (!Schema::hasColumn('system_logs', 'is_archived')) {
                    $table->boolean('is_archived')->default(false)->index()->comment('Soft delete for archival');
                }
            });
        }

        // Create logs archive table
        if (!Schema::hasTable('system_logs_archive')) {
            Schema::create('system_logs_archive', function (Blueprint $table) {
                $table->id();
                $table->date('archive_date')->index();
                $table->string('channel', 50)->index();
                $table->string('level', 20)->index();
                $table->string('event', 100)->nullable()->index();
                $table->longText('message');
                $table->json('context')->nullable();
                $table->string('admin_username', 255)->nullable();
                $table->string('method', 10)->nullable();
                $table->string('url', 255)->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->integer('status_code')->nullable();
                $table->integer('log_count')->default(1)->comment('Number of logs in this archive record');
                $table->timestamp('created_at')->index();
            });
        }

        // Create retention policies config table
        if (!Schema::hasTable('log_retention_policies')) {
            Schema::create('log_retention_policies', function (Blueprint $table) {
                $table->id();
                $table->string('channel', 50)->unique()->index();
                $table->string('level', 20)->comment('minimum level to retain');
                $table->integer('retention_days')->comment('Days to retain before archival');
                $table->integer('archive_days')->nullable()->comment('Days to keep archive before purge');
                $table->boolean('enabled')->default(true);
                $table->timestamps();
            });
        }

        // Create alerts table
        if (!Schema::hasTable('system_alerts')) {
            Schema::create('system_alerts', function (Blueprint $table) {
                $table->id();
                $table->string('alert_type', 100)->index()->comment('webhook_failed, job_failed, critical_error, etc');
                $table->string('severity', 20)->default('warning')->comment('info, warning, critical');
                $table->string('title', 255);
                $table->text('message');
                $table->json('context')->nullable()->comment('Event details, IDs, etc');
                $table->boolean('acknowledged')->default(false)->index();
                $table->timestamp('acknowledged_at')->nullable();
                $table->string('acknowledged_by')->nullable();
                $table->boolean('notified')->default(false);
                $table->timestamp('notified_at')->nullable();
                $table->string('notification_channels', 255)->nullable()->comment('email, webhook, ui, etc');
                $table->timestamps();

                $table->index(['alert_type', 'severity', 'created_at']);
                $table->index(['acknowledged', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('system_alerts');
        Schema::dropIfExists('log_retention_policies');
        Schema::dropIfExists('system_logs_archive');
        
        if (Schema::hasTable('system_logs')) {
            Schema::table('system_logs', function (Blueprint $table) {
                if (Schema::hasColumn('system_logs', 'retention_policy')) {
                    $table->dropColumn('retention_policy');
                }
                if (Schema::hasColumn('system_logs', 'archived_at')) {
                    $table->dropColumn('archived_at');
                }
                if (Schema::hasColumn('system_logs', 'is_archived')) {
                    $table->dropColumn('is_archived');
                }
            });
        }
    }
};
