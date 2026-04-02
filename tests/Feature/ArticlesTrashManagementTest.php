<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Modules\Articles\Models\Article;
use Tests\TestCase;

class ArticlesTrashManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

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

    public function test_empty_trash_route_force_deletes_trashed_articles(): void
    {
        $trashed = Article::query()->create([
            'title' => 'To trash article',
            'slug' => 'to-trash-article',
            'content_type' => 'article',
            'status' => 'draft',
        ]);
        $trashed->delete();

        $this->assertSoftDeleted('articles', ['id' => $trashed->id]);

        $response = $this->withAdminPermissions(['module.articles.trash'])
            ->delete($this->adminPath('/articles/trash/empty'));

        $response->assertRedirect();
        $this->assertDatabaseMissing('articles', ['id' => $trashed->id]);
    }

    public function test_articles_purge_trash_command_respects_days_option(): void
    {
        $old = Article::query()->create([
            'title' => 'Old trashed article',
            'slug' => 'old-trashed-article',
            'content_type' => 'article',
            'status' => 'draft',
        ]);
        $recent = Article::query()->create([
            'title' => 'Recent trashed article',
            'slug' => 'recent-trashed-article',
            'content_type' => 'article',
            'status' => 'draft',
        ]);

        $old->delete();
        $recent->delete();

        Article::withTrashed()->whereKey($old->id)->update(['deleted_at' => now()->subDays(40)]);
        Article::withTrashed()->whereKey($recent->id)->update(['deleted_at' => now()->subDays(5)]);

        $exit = Artisan::call('catmin:articles:purge-trash', ['--days' => 30]);

        $this->assertSame(0, $exit);
        $this->assertDatabaseMissing('articles', ['id' => $old->id]);
        $this->assertSoftDeleted('articles', ['id' => $recent->id]);
    }

    private function withAdminPermissions(array $permissions): self
    {
        return $this->withSession([
            'catmin_admin_authenticated' => true,
            'catmin_admin_login_at' => now()->timestamp,
            'catmin_admin_username' => 'articles-trash-test',
            'catmin_rbac_permissions' => $permissions,
            'catmin_rbac_roles' => [],
        ]);
    }

    private function adminPath(string $path): string
    {
        return '/' . trim((string) config('catmin.admin.path', 'admin'), '/') . $path;
    }
}
