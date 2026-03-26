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
        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();        // 'blog', 'media', 'users', etc.
            $table->string('name');                  // Display name
            $table->string('version')->default('1.0.0'); // Semantic version
            $table->text('description')->nullable();
            $table->string('status')->default('disabled'); // 'enabled', 'disabled', 'error'
            $table->json('config')->nullable();      // Module-specific config
            $table->json('dependencies')->nullable(); // Required modules
            $table->string('author')->nullable();
            $table->string('license')->nullable();
            $table->string('namespace')->nullable();  // PHP namespace
            $table->string('path')->nullable();      // Filesystem path
            $table->dateTime('enabled_at')->nullable();
            $table->dateTime('disabled_at')->nullable();
            $table->dateTime('installed_at')->nullable();

            $table->timestamps();
            
            // Indexes
            $table->index('slug');
            $table->index('status');
            $table->index('enabled_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('modules');
    }
};
