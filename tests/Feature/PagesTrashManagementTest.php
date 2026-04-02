<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Modules\Pages\Models\Page;
use Tests\TestCase;

class PagesTrashManagementTest extends TestCase
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
    }

    public function test_empty_trash_route_force_deletes_trashed_pages(): void
    {
        $trashed = Page::query()->create([
            'title' => 'To trash',
            'slug' => 'to-trash',
            'status' => 'draft',
        ]);
        $trashed->delete();

        $this->assertSoftDeleted('pages', ['id' => $trashed->id]);

        $response = $this->withAdminPermissions(['module.pages.trash'])
            ->delete($this->adminPath('/pages/trash/empty'));

        $response->assertRedirect();
        $this->assertDatabaseMissing('pages', ['id' => $trashed->id]);
    }

    public function test_pages_purge_trash_command_respects_days_option(): void
    {
        $old = Page::query()->create([
            'title' => 'Old trashed page',
            'slug' => 'old-trashed-page',
            'status' => 'draft',
        ]);
        $recent = Page::query()->create([
            'title' => 'Recent trashed page',
            'slug' => 'recent-trashed-page',
            'status' => 'draft',
        ]);

        $old->delete();
        $recent->delete();

        Page::withTrashed()->whereKey($old->id)->update(['deleted_at' => now()->subDays(40)]);
        Page::withTrashed()->whereKey($recent->id)->update(['deleted_at' => now()->subDays(5)]);

        $exit = Artisan::call('catmin:pages:purge-trash', ['--days' => 30]);

        $this->assertSame(0, $exit);
        $this->assertDatabaseMissing('pages', ['id' => $old->id]);
        $this->assertSoftDeleted('pages', ['id' => $recent->id]);
    }

    private function withAdminPermissions(array $permissions): self
    {
        return $this->withSession([
            'catmin_admin_authenticated' => true,
            'catmin_admin_login_at' => now()->timestamp,
            'catmin_admin_username' => 'pages-trash-test',
            'catmin_rbac_permissions' => $permissions,
            'catmin_rbac_roles' => [],
        ]);
    }

    private function adminPath(string $path): string
    {
        return '/' . trim((string) config('catmin.admin.path', 'admin'), '/') . $path;
    }
}
