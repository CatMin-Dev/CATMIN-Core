<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('shop_products')) {
            return;
        }

        Schema::table('shop_products', function (Blueprint $table): void {
            if (!Schema::hasColumn('shop_products', 'sku')) {
                $table->string('sku', 100)->nullable()->unique()->after('slug');
            }
            if (!Schema::hasColumn('shop_products', 'compare_at_price')) {
                $table->decimal('compare_at_price', 12, 2)->nullable()->after('price');
            }
            if (!Schema::hasColumn('shop_products', 'stock_quantity')) {
                $table->integer('stock_quantity')->default(0)->after('description');
            }
            if (!Schema::hasColumn('shop_products', 'low_stock_threshold')) {
                $table->integer('low_stock_threshold')->default(5)->after('stock_quantity');
            }
            if (!Schema::hasColumn('shop_products', 'visibility')) {
                $table->string('visibility', 20)->default('public')->after('status');
            }
            if (!Schema::hasColumn('shop_products', 'manage_stock')) {
                $table->boolean('manage_stock')->default(true)->after('visibility');
            }
            if (!Schema::hasColumn('shop_products', 'image_path')) {
                $table->string('image_path', 500)->nullable()->after('manage_stock');
            }
            if (!Schema::hasColumn('shop_products', 'product_type')) {
                $table->string('product_type', 20)->default('physical')->after('image_path');
            }
            if (!Schema::hasColumn('shop_products', 'published_at')) {
                $table->timestamp('published_at')->nullable()->after('product_type');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('shop_products')) {
            return;
        }

        Schema::table('shop_products', function (Blueprint $table): void {
            foreach ([
                'sku',
                'compare_at_price',
                'stock_quantity',
                'low_stock_threshold',
                'visibility',
                'manage_stock',
                'image_path',
                'product_type',
                'published_at',
            ] as $column) {
                if (Schema::hasColumn('shop_products', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
