<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('slider_items')) {
            return;
        }

        Schema::create('slider_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('slider_id')->constrained('sliders')->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->string('subtitle')->nullable();
            $table->text('content')->nullable();
            $table->string('cta_label')->nullable();
            $table->string('cta_url')->nullable();
            $table->unsignedBigInteger('media_id')->nullable();
            $table->string('media_url')->nullable();
            $table->enum('link_type', ['page', 'article', 'event', 'product', 'url'])->nullable();
            $table->unsignedBigInteger('link_id')->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['slider_id', 'position']);
            $table->index(['slider_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('slider_items');
    }
};
