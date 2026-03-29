<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('mailer_configs')) {
            Schema::table('mailer_configs', function (Blueprint $table): void {
                if (!Schema::hasColumn('mailer_configs', 'retry_max_attempts')) {
                    $table->unsignedSmallInteger('retry_max_attempts')->default(3)->after('sandbox_recipient');
                }
                if (!Schema::hasColumn('mailer_configs', 'retry_backoff_seconds')) {
                    $table->unsignedInteger('retry_backoff_seconds')->default(60)->after('retry_max_attempts');
                }
                if (!Schema::hasColumn('mailer_configs', 'fallback_driver')) {
                    $table->string('fallback_driver', 64)->nullable()->after('retry_backoff_seconds');
                }
                if (!Schema::hasColumn('mailer_configs', 'failure_alert_threshold')) {
                    $table->unsignedSmallInteger('failure_alert_threshold')->default(5)->after('fallback_driver');
                }
            });
        }

        if (Schema::hasTable('mailer_history')) {
            Schema::table('mailer_history', function (Blueprint $table): void {
                if (!Schema::hasColumn('mailer_history', 'next_retry_at')) {
                    $table->timestamp('next_retry_at')->nullable()->after('failed_at');
                }
                if (!Schema::hasColumn('mailer_history', 'provider_message_id')) {
                    $table->string('provider_message_id', 191)->nullable()->after('next_retry_at');
                }
                if (!Schema::hasColumn('mailer_history', 'original_recipient')) {
                    $table->string('original_recipient')->nullable()->after('provider_message_id');
                }
                if (!Schema::hasColumn('mailer_history', 'failure_class')) {
                    $table->string('failure_class', 64)->nullable()->after('error_message');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('mailer_history')) {
            Schema::table('mailer_history', function (Blueprint $table): void {
                foreach (['next_retry_at', 'provider_message_id', 'original_recipient', 'failure_class'] as $column) {
                    if (Schema::hasColumn('mailer_history', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('mailer_configs')) {
            Schema::table('mailer_configs', function (Blueprint $table): void {
                foreach (['retry_max_attempts', 'retry_backoff_seconds', 'fallback_driver', 'failure_alert_threshold'] as $column) {
                    if (Schema::hasColumn('mailer_configs', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
