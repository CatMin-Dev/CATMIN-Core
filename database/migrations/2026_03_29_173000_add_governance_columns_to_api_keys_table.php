<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('api_keys')) {
            return;
        }

        Schema::table('api_keys', function (Blueprint $table): void {
            if (!Schema::hasColumn('api_keys', 'usage_count')) {
                $table->unsignedBigInteger('usage_count')->default(0)->after('last_used_ip');
            }

            if (!Schema::hasColumn('api_keys', 'revoked_at')) {
                $table->timestamp('revoked_at')->nullable()->after('usage_count');
            }

            if (!Schema::hasColumn('api_keys', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('revoked_at');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('api_keys')) {
            return;
        }

        Schema::table('api_keys', function (Blueprint $table): void {
            foreach (['created_by', 'revoked_at', 'usage_count'] as $column) {
                if (Schema::hasColumn('api_keys', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
