<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user_profiles_extended')) {
            return;
        }

        Schema::create('user_profiles_extended', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('admin_user_id')->nullable()->index();
            $table->string('phone', 64)->nullable();
            $table->string('mobile', 64)->nullable();
            $table->string('company_name')->nullable();
            $table->string('address_line_1')->nullable();
            $table->string('address_line_2')->nullable();
            $table->string('postal_code', 32)->nullable();
            $table->string('city', 120)->nullable();
            $table->string('state', 120)->nullable();
            $table->string('country_code', 2)->nullable();
            $table->string('identity_type', 64)->nullable();
            $table->string('identity_number', 120)->nullable();
            $table->string('preferred_contact_method', 32)->nullable();
            $table->boolean('contact_opt_in')->default(false);
            $table->timestamps();

            $table->unique(['user_id']);
            $table->unique(['admin_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_profiles_extended');
    }
};
