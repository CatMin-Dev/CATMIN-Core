<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('articles')) {
            Schema::create('articles', function (Blueprint $table): void {
                $table->id();
                $table->string('title');
                $table->string('slug')->unique();
                $table->text('excerpt')->nullable();
                $table->longText('content')->nullable();
                $table->string('content_type', 32)->default('article');
                $table->string('status', 32)->default('draft');
                $table->timestamp('published_at')->nullable();
                $table->unsignedBigInteger('media_asset_id')->nullable();
                $table->unsignedBigInteger('seo_meta_id')->nullable();
                $table->json('taxonomy_snapshot')->nullable();
                $table->timestamps();

                $table->index('content_type');
                $table->index('status');
                $table->index('published_at');
            });
        }

        $slugMap = [];

        if (Schema::hasTable('blog_posts')) {
            $rows = DB::table('blog_posts')->orderBy('id')->get();
            foreach ($rows as $row) {
                $slug = $this->uniqueSlug((string) $row->slug, $slugMap);
                DB::table('articles')->insert([
                    'title' => $row->title,
                    'slug' => $slug,
                    'excerpt' => $row->excerpt,
                    'content' => $row->content,
                    'content_type' => 'article',
                    'status' => $row->status ?? 'draft',
                    'published_at' => $row->published_at,
                    'media_asset_id' => $row->media_asset_id,
                    'seo_meta_id' => $row->seo_meta_id,
                    'taxonomy_snapshot' => $row->taxonomy_snapshot,
                    'created_at' => $row->created_at ?? now(),
                    'updated_at' => $row->updated_at ?? now(),
                ]);
            }
        }

        if (Schema::hasTable('news_items')) {
            $rows = DB::table('news_items')->orderBy('id')->get();
            foreach ($rows as $row) {
                $slug = $this->uniqueSlug((string) $row->slug, $slugMap);
                DB::table('articles')->insert([
                    'title' => $row->title,
                    'slug' => $slug,
                    'excerpt' => $row->summary,
                    'content' => $row->content,
                    'content_type' => 'news',
                    'status' => $row->status ?? 'draft',
                    'published_at' => $row->published_at,
                    'media_asset_id' => $row->media_asset_id,
                    'seo_meta_id' => $row->seo_meta_id,
                    'taxonomy_snapshot' => json_encode(['category' => null, 'tags' => []], JSON_UNESCAPED_SLASHES),
                    'created_at' => $row->created_at ?? now(),
                    'updated_at' => $row->updated_at ?? now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }

    /**
     * @param array<string, bool> $slugMap
     */
    private function uniqueSlug(string $candidate, array &$slugMap): string
    {
        $base = trim($candidate) !== '' ? $candidate : 'article';
        $slug = $base;
        $i = 2;

        while ($slugMap[$slug] ?? DB::table('articles')->where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i;
            $i++;
        }

        $slugMap[$slug] = true;

        return $slug;
    }
};
