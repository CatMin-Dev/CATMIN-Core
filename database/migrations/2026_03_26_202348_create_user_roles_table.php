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
        Schema::create('user_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('role_id')->constrained()->onDelete('cascade');
            $table->dateTime('assigned_at')->nullable(); // When role was assigned
            $table->unsignedBigInteger('assigned_by_id')->nullable(); // Who assigned this role
            $table->text('notes')->nullable();       // Reason for assignment
            $table->timestamps();
            
            // Unique constraint to prevent duplicate assignments
            $table->unique(['user_id', 'role_id']);
            
            // Indexes
            $table->index('user_id');
            $table->index('role_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_roles');
    }
};
