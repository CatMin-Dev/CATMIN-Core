<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('geo_locations')) {
            return;
        }

        Schema::create('geo_locations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('geo_category_id')->nullable()->constrained('geo_categories')->nullOnDelete();
            $table->string('name', 191);
            $table->string('slug', 191)->unique();
            $table->text('description')->nullable();
            $table->string('address', 255)->nullable();
            $table->string('city', 120)->nullable();
            $table->string('country', 120)->nullable();
            $table->string('zip', 32)->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->string('phone', 64)->nullable();
            $table->string('email', 191)->nullable();
            $table->string('website', 255)->nullable();
            $table->text('opening_hours')->nullable();
            $table->string('status', 32)->default('published');
            $table->boolean('featured')->default(false);
            // Integration links
            $table->unsignedBigInteger('linked_event_id')->nullable()->index();
            $table->unsignedBigInteger('linked_shop_id')->nullable()->index();
            $table->unsignedBigInteger('linked_page_id')->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['status', 'featured']);
            $table->index('city');
            $table->index('country');
            $table->index(['lat', 'lng']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('geo_locations');
    }
};
