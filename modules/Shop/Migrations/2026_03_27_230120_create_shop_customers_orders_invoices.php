<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('shop_customers')) {
            Schema::create('shop_customers', function (Blueprint $table): void {
                $table->id();
                $table->string('first_name', 120);
                $table->string('last_name', 120)->nullable();
                $table->string('email', 191)->unique();
                $table->string('phone', 50)->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index('email');
            });
        }

        if (!Schema::hasTable('shop_orders')) {
            Schema::create('shop_orders', function (Blueprint $table): void {
                $table->id();
                $table->string('order_number', 50)->unique();
                $table->foreignId('customer_id')->nullable()->constrained('shop_customers')->nullOnDelete();
                $table->string('customer_email', 191);
                $table->string('customer_name', 191);
                $table->string('status', 30)->default('pending');
                $table->string('currency', 3)->default('EUR');
                $table->decimal('subtotal', 12, 2)->default(0);
                $table->decimal('tax_total', 12, 2)->default(0);
                $table->decimal('shipping_total', 12, 2)->default(0);
                $table->decimal('grand_total', 12, 2)->default(0);
                $table->timestamp('paid_at')->nullable();
                $table->timestamp('shipped_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->text('admin_notes')->nullable();
                $table->timestamps();

                $table->index(['status', 'created_at']);
            });
        }

        if (!Schema::hasTable('shop_order_items')) {
            Schema::create('shop_order_items', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('order_id')->constrained('shop_orders')->cascadeOnDelete();
                $table->foreignId('product_id')->nullable()->constrained('shop_products')->nullOnDelete();
                $table->string('product_name', 191);
                $table->string('product_sku', 100)->nullable();
                $table->integer('quantity')->default(1);
                $table->decimal('unit_price', 12, 2)->default(0);
                $table->decimal('line_total', 12, 2)->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('shop_invoices')) {
            Schema::create('shop_invoices', function (Blueprint $table): void {
                $table->id();
                $table->string('invoice_number', 50)->unique();
                $table->foreignId('order_id')->constrained('shop_orders')->cascadeOnDelete();
                $table->foreignId('customer_id')->nullable()->constrained('shop_customers')->nullOnDelete();
                $table->string('status', 30)->default('issued');
                $table->string('currency', 3)->default('EUR');
                $table->decimal('total', 12, 2)->default(0);
                $table->date('issued_on');
                $table->date('due_on')->nullable();
                $table->longText('html_snapshot')->nullable();
                $table->timestamps();

                $table->index(['order_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_invoices');
        Schema::dropIfExists('shop_order_items');
        Schema::dropIfExists('shop_orders');
        Schema::dropIfExists('shop_customers');
    }
};
