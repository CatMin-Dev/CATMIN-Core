<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table): void {
            if (!Schema::hasColumn('pages', 'media_asset_id')) {
                $table->unsignedBigInteger('media_asset_id')->nullable()->after('published_at');
                $table->index('media_asset_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table): void {
            if (Schema::hasColumn('pages', 'media_asset_id')) {
                $table->dropColumn('media_asset_id');
            }
        });
    }
};
