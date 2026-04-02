<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('crm_contacts')) {
            return;
        }

        Schema::create('crm_contacts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('crm_company_id')->nullable()->constrained('crm_companies')->nullOnDelete();
            $table->string('first_name', 120);
            $table->string('last_name', 120)->nullable();
            $table->string('email', 191)->nullable();
            $table->string('phone', 64)->nullable();
            $table->string('position', 120)->nullable();
            $table->string('status', 32)->default('lead');
            $table->text('tags')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('email');
            $table->index('first_name');
            $table->index('last_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_contacts');
    }
};
