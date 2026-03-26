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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();         // e.g., 'app.title', 'app.logo_url'
            $table->text('value')->nullable();       // Value stored as text/JSON
            $table->string('type')->default('string'); // 'string', 'boolean', 'json', 'integer'
            $table->string('group')->nullable();     // 'app', 'email', 'cache', 'modules'
            $table->text('description')->nullable();
            $table->boolean('is_public')->default(false);
            $table->timestamps();
            
            // Indexes
            $table->index(['key', 'group']);
            $table->index('group');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
