<?php

namespace Tests\Unit;

use App\Models\AdminUser;
use App\Services\SuperAdminGuardService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SuperAdminGuardServiceTest extends TestCase
{
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

        $this->createAdminUsersTable();
    }

    public function test_cannot_deactivate_last_active_super_admin(): void
    {
        $super = AdminUser::query()->create([
            'username' => 'root',
            'email' => 'root@test.local',
            'password' => Hash::make('Secret123!'),
            'is_active' => true,
            'is_super_admin' => true,
        ]);

        $result = app(SuperAdminGuardService::class)->canDeactivate($super, false);

        $this->assertFalse($result['allowed']);
    }

    public function test_can_deactivate_super_admin_if_another_active_super_admin_exists(): void
    {
        AdminUser::query()->create([
            'username' => 'root1',
            'email' => 'root1@test.local',
            'password' => Hash::make('Secret123!'),
            'is_active' => true,
            'is_super_admin' => true,
        ]);

        $target = AdminUser::query()->create([
            'username' => 'root2',
            'email' => 'root2@test.local',
            'password' => Hash::make('Secret123!'),
            'is_active' => true,
            'is_super_admin' => true,
        ]);

        $result = app(SuperAdminGuardService::class)->canDeactivate($target, false);

        $this->assertTrue($result['allowed']);
    }

    private function createAdminUsersTable(): void
    {
        Schema::dropAllTables();

        Schema::create('admin_users', function (Blueprint $table): void {
            $table->id();
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->string('password');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_super_admin')->default(false);
            $table->unsignedInteger('failed_login_attempts')->default(0);
            $table->timestamp('locked_until')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
}
