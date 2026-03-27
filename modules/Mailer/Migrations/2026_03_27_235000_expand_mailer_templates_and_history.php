<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('mailer_templates')) {
            Schema::table('mailer_templates', function (Blueprint $table): void {
                if (!Schema::hasColumn('mailer_templates', 'description')) {
                    $table->text('description')->nullable()->after('name');
                }
                if (!Schema::hasColumn('mailer_templates', 'available_variables')) {
                    $table->json('available_variables')->nullable()->after('body_text');
                }
                if (!Schema::hasColumn('mailer_templates', 'sample_payload')) {
                    $table->json('sample_payload')->nullable()->after('available_variables');
                }
            });
        }

        if (Schema::hasTable('mailer_history')) {
            Schema::table('mailer_history', function (Blueprint $table): void {
                if (!Schema::hasColumn('mailer_history', 'recipient_name')) {
                    $table->string('recipient_name')->nullable()->after('recipient');
                }
                if (!Schema::hasColumn('mailer_history', 'driver')) {
                    $table->string('driver', 64)->nullable()->after('template_code');
                }
                if (!Schema::hasColumn('mailer_history', 'variables_json')) {
                    $table->json('variables_json')->nullable()->after('status');
                }
                if (!Schema::hasColumn('mailer_history', 'body_html')) {
                    $table->longText('body_html')->nullable()->after('variables_json');
                }
                if (!Schema::hasColumn('mailer_history', 'body_text')) {
                    $table->longText('body_text')->nullable()->after('body_html');
                }
                if (!Schema::hasColumn('mailer_history', 'queued_at')) {
                    $table->timestamp('queued_at')->nullable()->after('body_text');
                }
                if (!Schema::hasColumn('mailer_history', 'failed_at')) {
                    $table->timestamp('failed_at')->nullable()->after('sent_at');
                }
                if (!Schema::hasColumn('mailer_history', 'attempts')) {
                    $table->unsignedSmallInteger('attempts')->default(0)->after('failed_at');
                }
                if (!Schema::hasColumn('mailer_history', 'is_test')) {
                    $table->boolean('is_test')->default(false)->after('attempts');
                }
                if (!Schema::hasColumn('mailer_history', 'trigger_source')) {
                    $table->string('trigger_source', 120)->nullable()->after('is_test');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('mailer_history')) {
            Schema::table('mailer_history', function (Blueprint $table): void {
                foreach (['recipient_name', 'driver', 'variables_json', 'body_html', 'body_text', 'queued_at', 'failed_at', 'attempts', 'is_test', 'trigger_source'] as $column) {
                    if (Schema::hasColumn('mailer_history', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('mailer_templates')) {
            Schema::table('mailer_templates', function (Blueprint $table): void {
                foreach (['description', 'available_variables', 'sample_payload'] as $column) {
                    if (Schema::hasColumn('mailer_templates', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
