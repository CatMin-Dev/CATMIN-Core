<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('article_categories')) {
            Schema::create('article_categories', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->unsignedBigInteger('parent_id')->nullable();
                $table->timestamps();

                $table->foreign('parent_id')->references('id')->on('article_categories')->nullOnDelete();
            });
        }

        if (!Schema::hasTable('tags')) {
            Schema::create('tags', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('article_tag')) {
            Schema::create('article_tag', function (Blueprint $table): void {
                $table->unsignedBigInteger('article_id');
                $table->unsignedBigInteger('tag_id');
                $table->timestamps();

                $table->primary(['article_id', 'tag_id']);
                $table->foreign('article_id')->references('id')->on('articles')->cascadeOnDelete();
                $table->foreign('tag_id')->references('id')->on('tags')->cascadeOnDelete();
            });
        }

        if (Schema::hasTable('articles') && !Schema::hasColumn('articles', 'article_category_id')) {
            Schema::table('articles', function (Blueprint $table): void {
                $table->unsignedBigInteger('article_category_id')->nullable()->after('content_type');
                $table->index('article_category_id');
            });
        }

        if (Schema::hasTable('articles')) {
            $this->migrateSnapshotData();
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('articles') && Schema::hasColumn('articles', 'article_category_id')) {
            Schema::table('articles', function (Blueprint $table): void {
                $table->dropIndex(['article_category_id']);
                $table->dropColumn('article_category_id');
            });
        }

        Schema::dropIfExists('article_tag');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('article_categories');
    }

    private function migrateSnapshotData(): void
    {
        $articles = DB::table('articles')
            ->select(['id', 'taxonomy_snapshot', 'article_category_id'])
            ->orderBy('id')
            ->get();

        foreach ($articles as $article) {
            $snapshot = json_decode((string) ($article->taxonomy_snapshot ?? ''), true);
            if (!is_array($snapshot)) {
                continue;
            }

            $categoryName = trim((string) ($snapshot['category'] ?? ''));
            if ($categoryName !== '' && empty($article->article_category_id)) {
                $categoryId = $this->firstOrCreateCategory($categoryName);
                DB::table('articles')->where('id', $article->id)->update(['article_category_id' => $categoryId]);
            }

            $tags = $snapshot['tags'] ?? [];
            if (!is_array($tags)) {
                continue;
            }

            foreach ($tags as $tagName) {
                $tagName = trim((string) $tagName);
                if ($tagName === '') {
                    continue;
                }

                $tagId = $this->firstOrCreateTag($tagName);
                DB::table('article_tag')->updateOrInsert(
                    ['article_id' => $article->id, 'tag_id' => $tagId],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }
        }
    }

    private function firstOrCreateCategory(string $name): int
    {
        $existing = DB::table('article_categories')->where('name', $name)->first();
        if ($existing) {
            return (int) $existing->id;
        }

        $slug = $this->uniqueSlug('article_categories', $name);

        return (int) DB::table('article_categories')->insertGetId([
            'name' => $name,
            'slug' => $slug,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function firstOrCreateTag(string $name): int
    {
        $existing = DB::table('tags')->where('name', $name)->first();
        if ($existing) {
            return (int) $existing->id;
        }

        $slug = $this->uniqueSlug('tags', $name);

        return (int) DB::table('tags')->insertGetId([
            'name' => $name,
            'slug' => $slug,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function uniqueSlug(string $table, string $value): string
    {
        $base = Str::slug($value);
        $base = $base !== '' ? $base : 'item';
        $slug = $base;
        $suffix = 1;

        while (DB::table($table)->where('slug', $slug)->exists()) {
            $suffix++;
            $slug = $base . '-' . $suffix;
        }

        return $slug;
    }
};