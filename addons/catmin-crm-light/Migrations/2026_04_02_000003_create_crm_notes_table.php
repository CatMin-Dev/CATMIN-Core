<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('crm_notes')) {
            return;
        }

        Schema::create('crm_notes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('crm_contact_id')->constrained('crm_contacts')->cascadeOnDelete();
            $table->string('type', 40)->default('note');
            $table->text('content');
            $table->string('module', 80)->nullable();
            $table->string('linked_type', 80)->nullable();
            $table->unsignedBigInteger('linked_id')->nullable();
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->timestamps();

            $table->index(['crm_contact_id', 'created_at']);
            $table->index(['module', 'linked_type']);
            $table->index('created_by_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_notes');
    }
};
