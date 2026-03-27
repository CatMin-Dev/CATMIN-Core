<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('webhooks')) {
            return;
        }

        Schema::table('webhooks', function (Blueprint $table): void {
            if (!Schema::hasColumn('webhooks', 'last_delivery_status')) {
                $table->unsignedSmallInteger('last_delivery_status')->nullable()->after('last_triggered_at');
            }
            if (!Schema::hasColumn('webhooks', 'last_delivery_error')) {
                $table->text('last_delivery_error')->nullable()->after('last_delivery_status');
            }
            if (!Schema::hasColumn('webhooks', 'last_delivery_at')) {
                $table->timestamp('last_delivery_at')->nullable()->after('last_delivery_error');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('webhooks')) {
            return;
        }

        Schema::table('webhooks', function (Blueprint $table): void {
            foreach (['last_delivery_status', 'last_delivery_error', 'last_delivery_at'] as $column) {
                if (Schema::hasColumn('webhooks', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
