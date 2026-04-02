<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('crm_companies')) {
            return;
        }

        Schema::create('crm_companies', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 191);
            $table->string('website', 255)->nullable();
            $table->string('industry', 120)->nullable();
            $table->string('email', 191)->nullable();
            $table->string('phone', 64)->nullable();
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('name');
            $table->index('email');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_companies');
    }
};
