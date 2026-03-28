<?php

namespace Tests\Feature\Admin;

use App\Models\AdminUser;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminTwoFactorPerAccountTest extends TestCase
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

        $this->createTables();
    }

    public function test_recovery_code_completes_pending_login_and_is_consumed(): void
    {
        $rawRecovery = 'ABCD1234';

        $admin = AdminUser::query()->create([
            'username' => 'admin2fa',
            'email' => 'admin2fa@test.local',
            'password' => Hash::make('Secret123!'),
            'is_active' => true,
            'is_super_admin' => true,
            'two_factor_enabled' => true,
            'two_factor_secret' => Crypt::encryptString('JBSWY3DPEHPK3PXP'),
            'two_factor_recovery_codes' => [hash('sha256', $rawRecovery)],
        ]);

        $response = $this
            ->withSession([
                'catmin_2fa_pending' => true,
                'catmin_2fa_pending_user_id' => $admin->id,
                'catmin_2fa_pending_username' => $admin->username,
            ])
            ->post('/admin/2fa/verify', [
                'otp' => $rawRecovery,
            ]);

        $response->assertRedirect(route('admin.index'));

        $response->assertSessionHas('catmin_admin_authenticated', true);
        $response->assertSessionHas('catmin_admin_user_id', $admin->id);

        $admin->refresh();
        $this->assertSame([], $admin->two_factor_recovery_codes ?? []);
    }

    private function createTables(): void
    {
        Schema::dropAllTables();

        Schema::create('admin_users', function (Blueprint $table): void {
            $table->id();
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->string('password');
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

        Schema::create('admin_sessions', function (Blueprint $table): void {
            $table->string('session_id', 128)->primary();
            $table->unsignedBigInteger('admin_user_id');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
        });
    }
}
