<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('admin_users')) {
            return;
        }

        Schema::table('admin_users', function (Blueprint $table): void {
            if (!Schema::hasColumn('admin_users', 'two_factor_enabled')) {
                $table->boolean('two_factor_enabled')->default(false)->after('is_super_admin');
            }

            if (!Schema::hasColumn('admin_users', 'two_factor_secret')) {
                $table->text('two_factor_secret')->nullable()->after('two_factor_enabled');
            }

            if (!Schema::hasColumn('admin_users', 'two_factor_recovery_codes')) {
                $table->json('two_factor_recovery_codes')->nullable()->after('two_factor_secret');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('admin_users')) {
            return;
        }

        Schema::table('admin_users', function (Blueprint $table): void {
            foreach (['two_factor_recovery_codes', 'two_factor_secret', 'two_factor_enabled'] as $column) {
                if (Schema::hasColumn('admin_users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
