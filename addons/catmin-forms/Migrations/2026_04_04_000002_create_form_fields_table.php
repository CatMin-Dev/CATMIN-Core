<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('form_fields')) {
            return;
        }

        Schema::create('form_fields', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('form_definition_id')->constrained('form_definitions')->cascadeOnDelete();
            $table->string('type', 40)->default('text');
            $table->string('label', 191);
            $table->string('key', 120);
            $table->boolean('is_required')->default(false);
            $table->json('options')->nullable();
            $table->string('validation_rules', 500)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['form_definition_id', 'sort_order']);
            $table->unique(['form_definition_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_fields');
    }
};
