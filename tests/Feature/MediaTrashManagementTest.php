<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Modules\Media\Models\MediaAsset;
use Tests\TestCase;

class MediaTrashManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (!Schema::hasTable('media_assets')) {
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
                $table->softDeletes();
            });
        }
    }

    public function test_empty_trash_route_force_deletes_trashed_media(): void
    {
        $trashed = MediaAsset::query()->create([
            'disk' => 'public',
            'path' => 'media/test/old-file.jpg',
            'filename' => 'old-file.jpg',
            'original_name' => 'old-file.jpg',
            'mime_type' => 'image/jpeg',
            'extension' => 'jpg',
            'size_bytes' => 1024,
        ]);
        $trashed->delete();

        $this->assertSoftDeleted('media_assets', ['id' => $trashed->id]);

        $response = $this->withAdminPermissions(['module.media.trash'])
            ->delete($this->adminPath('/media/trash/empty'));

        $response->assertRedirect();
        $this->assertDatabaseMissing('media_assets', ['id' => $trashed->id]);
    }

    public function test_media_purge_trash_command_respects_days_option(): void
    {
        $old = MediaAsset::query()->create([
            'disk' => 'public',
            'path' => 'media/test/old.jpg',
            'filename' => 'old.jpg',
            'original_name' => 'old.jpg',
            'mime_type' => 'image/jpeg',
            'extension' => 'jpg',
            'size_bytes' => 2048,
        ]);
        $recent = MediaAsset::query()->create([
            'disk' => 'public',
            'path' => 'media/test/recent.jpg',
            'filename' => 'recent.jpg',
            'original_name' => 'recent.jpg',
            'mime_type' => 'image/jpeg',
            'extension' => 'jpg',
            'size_bytes' => 4096,
        ]);

        $old->delete();
        $recent->delete();

        MediaAsset::withTrashed()->whereKey($old->id)->update(['deleted_at' => now()->subDays(40)]);
        MediaAsset::withTrashed()->whereKey($recent->id)->update(['deleted_at' => now()->subDays(5)]);

        $exit = Artisan::call('catmin:media:purge-trash', ['--days' => 30]);

        $this->assertSame(0, $exit);
        $this->assertDatabaseMissing('media_assets', ['id' => $old->id]);
        $this->assertSoftDeleted('media_assets', ['id' => $recent->id]);
    }

    private function withAdminPermissions(array $permissions): self
    {
        return $this->withSession([
            'catmin_admin_authenticated' => true,
            'catmin_admin_login_at' => now()->timestamp,
            'catmin_admin_username' => 'media-trash-test',
            'catmin_rbac_permissions' => $permissions,
            'catmin_rbac_roles' => [],
        ]);
    }

    private function adminPath(string $path): string
    {
        return '/' . trim((string) config('catmin.admin.path', 'admin'), '/') . $path;
    }
}
