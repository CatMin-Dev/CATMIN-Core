<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Modules\Articles\Models\Article;
use Modules\Pages\Models\Page;
use Tests\TestCase;

class ContentPreviewAndSchedulingTest extends TestCase
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
                $table->string('status', 32)->default('draft');
                $table->timestamp('published_at')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('articles')) {
            Schema::create('articles', function (Blueprint $table): void {
                $table->id();
                $table->string('content_type', 32)->default('article');
                $table->string('title');
                $table->string('slug')->unique();
                $table->text('excerpt')->nullable();
                $table->longText('content')->nullable();
                $table->string('status', 32)->default('draft');
                $table->timestamp('published_at')->nullable();
                $table->json('taxonomy_snapshot')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    public function test_pages_preview_requires_create_or_edit_permission(): void
    {
        $response = $this->withAdminPermissions(['module.pages.list'])
            ->post($this->adminPath('/pages/preview'), [
                'title' => 'Preview denied',
                'content' => '<p>body</p>',
            ]);

        $response->assertForbidden();
    }

    public function test_scheduled_command_publishes_due_page_and_article(): void
    {
        $page = Page::query()->create([
            'title' => 'Scheduled page',
            'slug' => 'scheduled-page',
            'excerpt' => '',
            'content' => '<p>p</p>',
            'status' => 'scheduled',
            'published_at' => now()->subMinute(),
        ]);

        $article = Article::query()->create([
            'title' => 'Scheduled article',
            'slug' => 'scheduled-article',
            'excerpt' => '',
            'content' => '<p>a</p>',
            'content_type' => 'article',
            'status' => 'scheduled',
            'published_at' => now()->subMinute(),
            'taxonomy_snapshot' => ['category' => null, 'tags' => []],
        ]);

        $exitCode = Artisan::call('catmin:content:publish-scheduled');

        $this->assertSame(0, $exitCode);
        $this->assertSame('published', (string) $page->fresh()?->status);
        $this->assertSame('published', (string) $article->fresh()?->status);
    }

    private function withAdminPermissions(array $permissions, array $roles = []): self
    {
        return $this->withSession([
            'catmin_admin_authenticated' => true,
            'catmin_admin_login_at' => now()->timestamp,
            'catmin_admin_username' => 'preview-test',
            'catmin_rbac_permissions' => $permissions,
            'catmin_rbac_roles' => $roles,
        ]);
    }

    private function adminPath(string $path): string
    {
        return '/' . trim((string) config('catmin.admin.path', 'admin'), '/') . $path;
    }
}
