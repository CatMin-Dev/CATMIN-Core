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
            if (!Schema::hasColumn('admin_users', 'contact_email')) {
                $table->string('contact_email')->nullable()->after('email');
            }

            if (!Schema::hasColumn('admin_users', 'phone')) {
                $table->string('phone', 64)->nullable()->after('last_name');
            }

            if (!Schema::hasColumn('admin_users', 'avatar_media_asset_id')) {
                $table->unsignedBigInteger('avatar_media_asset_id')->nullable()->after('phone');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('admin_users')) {
            return;
        }

        Schema::table('admin_users', function (Blueprint $table): void {
            foreach (['avatar_media_asset_id', 'phone', 'contact_email'] as $column) {
                if (Schema::hasColumn('admin_users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
