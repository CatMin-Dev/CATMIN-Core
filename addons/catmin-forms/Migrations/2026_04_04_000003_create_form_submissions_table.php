<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('form_submissions')) {
            return;
        }

        Schema::create('form_submissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('form_definition_id')->constrained('form_definitions')->cascadeOnDelete();
            $table->json('payload');
            $table->string('source', 80)->default('public');
            $table->string('status', 30)->default('new');
            $table->unsignedBigInteger('linked_contact_id')->nullable();
            $table->dateTime('processed_at')->nullable();
            $table->string('ip_hash', 128)->nullable();
            $table->timestamps();

            $table->index(['form_definition_id', 'status']);
            $table->index(['source', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_submissions');
    }
};
