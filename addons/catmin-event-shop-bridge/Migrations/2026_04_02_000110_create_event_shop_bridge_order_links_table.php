<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('event_shop_bridge_order_links')) {
            Schema::create('event_shop_bridge_order_links', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('ticket_type_id')->constrained('event_shop_bridge_ticket_types')->cascadeOnDelete();
                $table->foreignId('shop_order_id')->constrained('shop_orders')->cascadeOnDelete();
                $table->foreignId('shop_order_item_id')->constrained('shop_order_items')->cascadeOnDelete();
                $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
                $table->foreignId('event_participant_id')->nullable()->constrained('event_participants')->nullOnDelete();
                $table->foreignId('event_ticket_id')->nullable()->constrained('event_tickets')->nullOnDelete();
                $table->unsignedInteger('unit_index')->default(1);
                $table->string('source_key', 191)->unique();
                $table->string('status', 30)->default('pending');
                $table->string('customer_email', 191)->nullable();
                $table->string('customer_name', 191)->nullable();
                $table->text('integration_error')->nullable();
                $table->timestamp('issued_at')->nullable();
                $table->timestamp('cancelled_at')->nullable();
                $table->timestamps();

                $table->index(['shop_order_id', 'status']);
                $table->index(['ticket_type_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('event_shop_bridge_order_links');
    }
};