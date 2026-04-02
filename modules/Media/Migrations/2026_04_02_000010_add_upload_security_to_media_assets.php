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
            if (!Schema::hasColumn('media_assets', 'real_mime')) {
                $table->string('real_mime', 128)->nullable()->after('mime_type')
                    ->comment('Real MIME detected by finfo (magic bytes)');
            }
            if (!Schema::hasColumn('media_assets', 'quarantine_at')) {
                $table->timestamp('quarantine_at')->nullable()->after('real_mime')
                    ->comment('Set when file is quarantined for review');
            }
            if (!Schema::hasColumn('media_assets', 'quarantine_reason')) {
                $table->text('quarantine_reason')->nullable()->after('quarantine_at');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('media_assets')) {
            return;
        }

        Schema::table('media_assets', function (Blueprint $table): void {
            foreach (['real_mime', 'quarantine_at', 'quarantine_reason'] as $column) {
                if (Schema::hasColumn('media_assets', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
