<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Modules\Articles\Models\Article;
use Modules\Articles\Models\ArticleCategory;
use Modules\Articles\Models\Tag;
use Modules\Articles\Services\ArticleAdminService;
use Tests\TestCase;

class ArticleTaxonomyManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

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
                $table->string('meta_title')->nullable();
                $table->string('meta_description')->nullable();
                $table->json('taxonomy_snapshot')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    public function test_can_create_category_and_tag(): void
    {
        $this->runTaxonomyMigration();

        $this->withAdminPermissions(['module.articles.config'])
            ->post($this->adminPath('/articles/categories'), [
                'name' => 'Actualites',
                'slug' => 'actualites',
            ])
            ->assertRedirect();

        $this->withAdminPermissions(['module.articles.config'])
            ->post($this->adminPath('/articles/tags'), [
                'name' => 'Laravel',
                'slug' => 'laravel',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('article_categories', ['slug' => 'actualites']);
        $this->assertDatabaseHas('tags', ['slug' => 'laravel']);
    }

    public function test_article_can_be_assigned_category_and_tags(): void
    {
        $this->runTaxonomyMigration();

        $category = ArticleCategory::query()->create(['name' => 'Guides', 'slug' => 'guides']);
        $tagA = Tag::query()->create(['name' => 'PHP', 'slug' => 'php']);
        $tagB = Tag::query()->create(['name' => 'CMS', 'slug' => 'cms']);

        $article = app(ArticleAdminService::class)->create([
            'title' => 'Guide CMS',
            'slug' => 'guide-cms',
            'content_type' => 'article',
            'article_category_id' => $category->id,
            'tag_ids' => [$tagA->id, $tagB->id],
            'status' => 'draft',
        ]);

        $article->load('tags');

        $this->assertSame($category->id, $article->article_category_id);
        $this->assertCount(2, $article->tags);
    }

    public function test_listing_filters_by_category_and_tag(): void
    {
        $this->runTaxonomyMigration();

        $categoryA = ArticleCategory::query()->create(['name' => 'Tech', 'slug' => 'tech']);
        $categoryB = ArticleCategory::query()->create(['name' => 'Business', 'slug' => 'business']);
        $tagA = Tag::query()->create(['name' => 'PHP', 'slug' => 'php']);
        $tagB = Tag::query()->create(['name' => 'Finance', 'slug' => 'finance']);

        $articleA = Article::query()->create([
            'title' => 'Article Tech',
            'slug' => 'article-tech',
            'content_type' => 'article',
            'article_category_id' => $categoryA->id,
            'status' => 'draft',
        ]);
        $articleB = Article::query()->create([
            'title' => 'Article Business',
            'slug' => 'article-business',
            'content_type' => 'article',
            'article_category_id' => $categoryB->id,
            'status' => 'draft',
        ]);
        $articleA->tags()->attach($tagA->id);
        $articleB->tags()->attach($tagB->id);

        $service = app(ArticleAdminService::class);

        $categoryFiltered = $service->listing(null, 25, 'active', $categoryA->id, null);
        $tagFiltered = $service->listing(null, 25, 'active', null, $tagB->id);

        $this->assertSame(['Article Tech'], collect($categoryFiltered->items())->pluck('title')->all());
        $this->assertSame(['Article Business'], collect($tagFiltered->items())->pluck('title')->all());
    }

    public function test_snapshot_migration_moves_category_and_tags(): void
    {
        Article::query()->create([
            'title' => 'Legacy Article',
            'slug' => 'legacy-article',
            'content_type' => 'article',
            'status' => 'draft',
            'taxonomy_snapshot' => [
                'category' => 'Legacy Category',
                'tags' => ['Tag One', 'Tag Two'],
            ],
        ]);

        $this->runTaxonomyMigration();

        $article = Article::query()->where('slug', 'legacy-article')->firstOrFail();
        $article->load('category', 'tags');

        $this->assertSame('Legacy Category', $article->category?->name);
        $this->assertCount(2, $article->tags);
        $this->assertSame(['Tag One', 'Tag Two'], $article->tags->pluck('name')->sort()->values()->all());
    }

    private function runTaxonomyMigration(): void
    {
        if (Schema::hasTable('article_categories') && Schema::hasTable('tags') && Schema::hasTable('article_tag') && Schema::hasColumn('articles', 'article_category_id')) {
            return;
        }

        $migration = require base_path('modules/Articles/Migrations/2026_04_02_000010_create_article_taxonomy_tables.php');
        $migration->up();
    }

    private function withAdminPermissions(array $permissions): self
    {
        return $this->withSession([
            'catmin_admin_authenticated' => true,
            'catmin_admin_login_at' => now()->timestamp,
            'catmin_admin_username' => 'taxonomy-test',
            'catmin_rbac_permissions' => $permissions,
            'catmin_rbac_roles' => [],
        ]);
    }

    private function adminPath(string $path): string
    {
        return '/' . trim((string) config('catmin.admin.path', 'admin'), '/') . $path;
    }
}