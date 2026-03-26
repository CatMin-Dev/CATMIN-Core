<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();        // e.g., 'admin', 'editor', 'viewer'
            $table->string('display_name')->nullable(); // Human-readable name
            $table->text('description')->nullable();
            $table->json('permissions')->nullable();  // ['create', 'read', 'update', 'delete', 'publish']
            $table->integer('priority')->default(0);  // For role hierarchy
            $table->boolean('is_system')->default(false); // Cannot be deleted if true
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Indexes
            $table->index('name');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
