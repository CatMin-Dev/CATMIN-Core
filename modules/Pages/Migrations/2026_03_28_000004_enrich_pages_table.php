<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table): void {
            if (!Schema::hasColumn('pages', 'excerpt')) {
                $table->text('excerpt')->nullable()->after('slug');
            }
            if (!Schema::hasColumn('pages', 'meta_title')) {
                $table->string('meta_title', 255)->nullable()->after('published_at');
            }
            if (!Schema::hasColumn('pages', 'meta_description')) {
                $table->string('meta_description', 320)->nullable()->after('meta_title');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table): void {
            $table->dropColumn(['excerpt', 'meta_title', 'meta_description']);
        });
    }
};
