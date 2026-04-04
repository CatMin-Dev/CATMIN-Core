<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('crm_interactions')) {
            return;
        }

        Schema::create('crm_interactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('crm_contact_id')->constrained('crm_contacts')->cascadeOnDelete();
            $table->foreignId('crm_company_id')->nullable()->constrained('crm_companies')->nullOnDelete();
            $table->string('type', 40)->default('note');
            $table->string('subject', 191)->nullable();
            $table->text('content');
            $table->string('source', 80)->nullable();
            $table->dateTime('happened_at')->nullable();
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->timestamps();

            $table->index(['crm_contact_id', 'happened_at']);
            $table->index(['type', 'created_at']);
            $table->index('source');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_interactions');
    }
};
