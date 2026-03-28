<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table): void {
            if (!Schema::hasColumn('settings', 'label')) {
                $table->string('label')->nullable()->after('key');
            }

            if (!Schema::hasColumn('settings', 'is_editable')) {
                $table->boolean('is_editable')->default(true)->after('is_public');
            }

            if (!Schema::hasColumn('settings', 'options')) {
                // JSON list of allowed values (for select-type settings)
                $table->text('options')->nullable()->after('is_editable');
            }

            if (!Schema::hasColumn('settings', 'validation_rules')) {
                // Serialised Laravel validation rule string, e.g. "required|email|max:255"
                $table->string('validation_rules', 500)->nullable()->after('options');
            }
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table): void {
            $table->dropColumn(['label', 'is_editable', 'options', 'validation_rules']);
        });
    }
};
