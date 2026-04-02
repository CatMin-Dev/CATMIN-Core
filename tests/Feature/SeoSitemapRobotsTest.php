<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Modules\Articles\Models\Article;
use Modules\Pages\Models\Page;
use Modules\SEO\Services\RobotsService;
use Modules\SEO\Services\SitemapService;
use Tests\TestCase;

class SeoSitemapRobotsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (!Schema::hasTable('pages')) {
            Schema::create('pages', function (Blueprint $table): void {
                $table->id();
                $table->string('title');
                $table->string('slug')->unique();
                $table->text('excerpt')->nullable();
                $table->longText('content')->nullable();
                $table->string('status', 20)->default('draft');
                $table->timestamp('published_at')->nullable();
                $table->unsignedBigInteger('media_asset_id')->nullable();
                $table->string('meta_title')->nullable();
                $table->string('meta_description')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('articles')) {
            Schema::create('articles', function (Blueprint $table): void {
                $table->id();
                $table->string('title');
                $table->string('slug')->unique();
                $table->text('excerpt')->nullable();
                $table->longText('content')->nullable();
                $table->string('content_type', 20)->default('article');
                $table->string('status', 20)->default('draft');
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

    public function test_sitemap_xml_includes_published_pages_and_articles(): void
    {
        Page::query()->create([
            'title' => 'Page Publish',
            'slug' => 'page-publish',
            'excerpt' => '',
            'content' => '',
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        Page::query()->create([
            'title' => 'Page Draft',
            'slug' => 'page-draft',
            'excerpt' => '',
            'content' => '',
            'status' => 'draft',
            'published_at' => null,
        ]);

        Article::query()->create([
            'title' => 'Article Publish',
            'slug' => 'article-publish',
            'excerpt' => '',
            'content' => '',
            'content_type' => 'article',
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        Article::query()->create([
            'title' => 'Article Draft',
            'slug' => 'article-draft',
            'excerpt' => '',
            'content' => '',
            'content_type' => 'article',
            'status' => 'draft',
            'published_at' => null,
        ]);

        $xml = app(SitemapService::class)->refresh();

        $this->assertStringContainsString('/page/page-publish', $xml);
        $this->assertStringContainsString('/article/article-publish', $xml);
        $this->assertStringNotContainsString('/page/page-draft', $xml);
        $this->assertStringNotContainsString('/article/article-draft', $xml);
    }

    public function test_robots_txt_fallback_and_custom_content(): void
    {
        $service = app(RobotsService::class);
        $fallback = $service->getContent();

        $this->assertStringContainsString('User-agent: *', $fallback);
        $this->assertStringContainsString('Allow: /', $fallback);
        $this->assertStringContainsString('Sitemap: /sitemap.xml', $fallback);

        Cache::forever((string) config('catmin.settings.cache_key', 'catmin.settings'), [
            'seo.robots_txt' => "User-agent: *\nDisallow: /admin\n",
        ]);

        $custom = $service->getContent();
        $this->assertStringContainsString('Disallow: /admin', $custom);
    }

    public function test_manual_sitemap_refresh_command_runs(): void
    {
        $this->artisan('catmin:seo:sitemap:refresh')
            ->expectsOutput('Sitemap regenere avec succes.')
            ->assertSuccessful();
    }

    public function test_admin_routes_emit_noindex_header(): void
    {
        $adminPath = '/' . trim((string) config('catmin.admin.path', 'admin'), '/');

        $response = $this->get($adminPath . '/login');

        $response->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');
    }
}
