<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('blog_posts')) {
            return;
        }

        Schema::create('blog_posts', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('content')->nullable();
            $table->string('status', 32)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->unsignedBigInteger('media_asset_id')->nullable();
            $table->unsignedBigInteger('seo_meta_id')->nullable();
            $table->json('taxonomy_snapshot')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('published_at');
            $table->index('media_asset_id');
            $table->index('seo_meta_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_posts');
    }
};
