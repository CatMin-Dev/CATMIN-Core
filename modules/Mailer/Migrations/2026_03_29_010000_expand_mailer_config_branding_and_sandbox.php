<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('mailer_configs')) {
            return;
        }

        Schema::table('mailer_configs', function (Blueprint $table): void {
            if (!Schema::hasColumn('mailer_configs', 'brand_name')) {
                $table->string('brand_name')->nullable()->after('reply_to_email');
            }
            if (!Schema::hasColumn('mailer_configs', 'brand_logo_url')) {
                $table->string('brand_logo_url', 500)->nullable()->after('brand_name');
            }
            if (!Schema::hasColumn('mailer_configs', 'brand_primary_color')) {
                $table->string('brand_primary_color', 7)->default('#0d6efd')->after('brand_logo_url');
            }
            if (!Schema::hasColumn('mailer_configs', 'brand_footer_text')) {
                $table->text('brand_footer_text')->nullable()->after('brand_primary_color');
            }
            if (!Schema::hasColumn('mailer_configs', 'sandbox_mode')) {
                $table->boolean('sandbox_mode')->default(false)->after('brand_footer_text');
            }
            if (!Schema::hasColumn('mailer_configs', 'sandbox_recipient')) {
                $table->string('sandbox_recipient')->nullable()->after('sandbox_mode');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('mailer_configs')) {
            return;
        }

        Schema::table('mailer_configs', function (Blueprint $table): void {
            foreach (['brand_name', 'brand_logo_url', 'brand_primary_color', 'brand_footer_text', 'sandbox_mode', 'sandbox_recipient'] as $column) {
                if (Schema::hasColumn('mailer_configs', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
