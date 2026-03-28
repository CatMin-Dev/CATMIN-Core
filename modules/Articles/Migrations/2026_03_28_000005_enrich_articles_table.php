<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table): void {
            if (!Schema::hasColumn('articles', 'meta_title')) {
                $table->string('meta_title', 255)->nullable()->after('seo_meta_id');
            }
            if (!Schema::hasColumn('articles', 'meta_description')) {
                $table->string('meta_description', 320)->nullable()->after('meta_title');
            }
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table): void {
            $table->dropColumn(['meta_title', 'meta_description']);
        });
    }
};
