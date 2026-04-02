<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('webhook_deliveries')) {
            return;
        }

        Schema::table('webhook_deliveries', function (Blueprint $table): void {
            if (!Schema::hasColumn('webhook_deliveries', 'dead_letter_at')) {
                $table->timestamp('dead_letter_at')->nullable()->after('sent_at');
            }
            if (!Schema::hasColumn('webhook_deliveries', 'dlq_reason')) {
                $table->text('dlq_reason')->nullable()->after('dead_letter_at');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('webhook_deliveries')) {
            return;
        }

        Schema::table('webhook_deliveries', function (Blueprint $table): void {
            foreach (['dead_letter_at', 'dlq_reason'] as $column) {
                if (Schema::hasColumn('webhook_deliveries', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
