<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('shop_invoice_settings')) {
            Schema::create('shop_invoice_settings', function (Blueprint $table): void {
                $table->id();
                $table->string('company_name')->default('');
                $table->text('company_address')->nullable();
                $table->string('company_siret')->nullable();
                $table->string('company_vat')->nullable();
                $table->string('company_iban')->nullable();
                $table->string('company_phone')->nullable();
                $table->string('company_email')->nullable();
                $table->string('company_logo_url')->nullable();
                $table->text('invoice_footer')->nullable();
                $table->unsignedSmallInteger('payment_terms_days')->default(30);
                $table->string('currency')->default('EUR');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_invoice_settings');
    }
};
