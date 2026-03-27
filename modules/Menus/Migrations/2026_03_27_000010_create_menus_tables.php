<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('menus')) {
            Schema::create('menus', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('location', 80)->default('primary');
                $table->string('status', 32)->default('active');
                $table->timestamps();

                $table->index(['location', 'status']);
            });
        }

        if (!Schema::hasTable('menu_items')) {
            Schema::create('menu_items', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('menu_id');
                $table->unsignedBigInteger('parent_id')->nullable();
                $table->string('label');
                $table->string('url')->nullable();
                $table->unsignedBigInteger('page_id')->nullable();
                $table->string('type', 32)->default('url');
                $table->unsignedInteger('sort_order')->default(0);
                $table->string('status', 32)->default('active');
                $table->timestamps();

                $table->index(['menu_id', 'status']);
                $table->index(['parent_id', 'sort_order']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_items');
        Schema::dropIfExists('menus');
    }
};
