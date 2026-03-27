<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('media_assets')) {
            return;
        }

        Schema::create('media_assets', function (Blueprint $table): void {
            $table->id();
            $table->string('disk')->default('public');
            $table->string('path');
            $table->string('filename');
            $table->string('original_name');
            $table->string('mime_type', 128)->nullable();
            $table->string('extension', 32)->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->string('alt_text')->nullable();
            $table->text('caption')->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('uploaded_by_id')->nullable();
            $table->timestamps();

            $table->index('mime_type');
            $table->index('extension');
            $table->index('uploaded_by_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_assets');
    }
};
