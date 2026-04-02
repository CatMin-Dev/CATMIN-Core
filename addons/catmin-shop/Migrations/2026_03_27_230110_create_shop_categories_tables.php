<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('shop_categories')) {
            Schema::create('shop_categories', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('parent_id')->nullable()->constrained('shop_categories')->nullOnDelete();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->integer('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['parent_id', 'sort_order']);
            });
        }

        if (!Schema::hasTable('shop_category_product')) {
            Schema::create('shop_category_product', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('category_id')->constrained('shop_categories')->cascadeOnDelete();
                $table->foreignId('product_id')->constrained('shop_products')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['category_id', 'product_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_category_product');
        Schema::dropIfExists('shop_categories');
    }
};
