<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('media_assets')) {
            return;
        }

        Schema::table('media_assets', function (Blueprint $table): void {
            if (!Schema::hasColumn('media_assets', 'deleted_at')) {
                $table->softDeletes();
                $table->index('deleted_at');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('media_assets')) {
            return;
        }

        Schema::table('media_assets', function (Blueprint $table): void {
            if (Schema::hasColumn('media_assets', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
