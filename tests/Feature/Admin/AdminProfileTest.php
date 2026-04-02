<?php

namespace Tests\Feature\Admin;

use App\Models\AdminUser;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminProfileTest extends TestCase
{
    protected AdminUser $admin;

    protected function setUp(): void
    {
        parent::setUp();

        if (!extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('pdo_sqlite extension is not available.');
        }

        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', ':memory:');

        app('db')->purge('sqlite');
        app('db')->reconnect('sqlite');

        $this->createTables();

        $this->admin = AdminUser::query()->create([
            'username' => 'profile_admin',
            'email' => 'profile_admin@test.local',
            'password' => Hash::make('OldPassword123!'),
            'first_name' => 'Old',
            'last_name' => 'Name',
            'is_active' => true,
            'is_super_admin' => true,
            'two_factor_enabled' => false,
        ]);
    }

    public function test_update_profil(): void
    {
        $response = $this->authorizedSession()->put('/admin/profile', [
            'first_name' => 'Jean',
            'last_name' => 'Dupont',
            'contact_email' => 'jean.dupont@example.com',
            'phone' => '+33600000000',
        ]);

        $response->assertRedirect(route('admin.profile.show'));

        $this->admin->refresh();
        $this->assertSame('Jean', $this->admin->first_name);
        $this->assertSame('Dupont', $this->admin->last_name);
        $this->assertSame('jean.dupont@example.com', $this->admin->contact_email);
        $this->assertSame('+33600000000', $this->admin->phone);
    }

    public function test_update_avatar(): void
    {
        $assetId = (int) \DB::table('media_assets')->insertGetId([
            'disk' => 'public',
            'path' => 'media/placeholders/avatar.jpg',
            'filename' => 'avatar.jpg',
            'original_name' => 'avatar.jpg',
            'mime_type' => 'image/jpeg',
            'extension' => 'jpg',
            'size_bytes' => 0,
            'alt_text' => 'Avatar',
            'caption' => 'Avatar',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->authorizedSession()->put('/admin/profile/avatar', [
            'avatar_media_asset_id' => $assetId,
        ]);

        $response->assertRedirect(route('admin.profile.show'));

        $this->admin->refresh();
        $this->assertSame($assetId, (int) $this->admin->avatar_media_asset_id);
    }

    public function test_change_password(): void
    {
        $response = $this->authorizedSession()->put('/admin/profile/password', [
            'current_password' => 'OldPassword123!',
            'new_password' => 'NewPassword1234!',
            'new_password_confirmation' => 'NewPassword1234!',
        ]);

        $response->assertRedirect(route('admin.profile.show'));

        $this->admin->refresh();
        $this->assertTrue(Hash::check('NewPassword1234!', (string) $this->admin->password));
    }

    public function test_permissions_profil(): void
    {
        $response = $this->withSession([
            'catmin_admin_authenticated' => true,
            'catmin_admin_user_id' => $this->admin->id,
            'catmin_admin_username' => $this->admin->username,
            'catmin_rbac_permissions' => [],
            'catmin_rbac_roles' => [],
        ])->get('/admin/profile');

        $response->assertStatus(403);
    }

    private function authorizedSession(): self
    {
        return $this->withSession([
            'catmin_admin_authenticated' => true,
            'catmin_admin_user_id' => $this->admin->id,
            'catmin_admin_username' => $this->admin->username,
            'catmin_rbac_permissions' => ['module.core.list'],
            'catmin_rbac_roles' => ['admin'],
        ]);
    }

    private function createTables(): void
    {
        Schema::dropAllTables();

        Schema::create('admin_users', function (Blueprint $table): void {
            $table->id();
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->string('contact_email')->nullable();
            $table->string('password');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('phone', 64)->nullable();
            $table->unsignedBigInteger('avatar_media_asset_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_super_admin')->default(false);
            $table->boolean('two_factor_enabled')->default(false);
            $table->text('two_factor_secret')->nullable();
            $table->json('two_factor_recovery_codes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->integer('failed_login_attempts')->default(0);
            $table->timestamp('locked_until')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('media_assets', function (Blueprint $table): void {
            $table->id();
            $table->string('disk', 32)->default('public');
            $table->string('path');
            $table->string('filename');
            $table->string('original_name')->nullable();
            $table->string('mime_type', 120)->nullable();
            $table->string('extension', 20)->nullable();
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
