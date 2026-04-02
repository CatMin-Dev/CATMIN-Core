<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('event_shop_bridge_ticket_types')) {
            Schema::create('event_shop_bridge_ticket_types', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
                $table->foreignId('shop_product_id')->nullable()->constrained('shop_products')->nullOnDelete();
                $table->string('name', 191);
                $table->string('slug', 191);
                $table->string('sku', 100)->unique();
                $table->decimal('price', 12, 2)->default(0);
                $table->unsignedInteger('allocation')->nullable();
                $table->boolean('auto_cancel_on_order_cancel')->default(true);
                $table->string('status', 30)->default('active');
                $table->text('description')->nullable();
                $table->timestamps();

                $table->unique(['event_id', 'slug']);
                $table->index(['event_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('event_shop_bridge_ticket_types');
    }
};