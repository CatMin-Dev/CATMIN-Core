<?php

namespace Tests\Feature\Admin;

use App\Mail\AdminPasswordResetMail;
use App\Models\AdminUser;
use App\Services\AdminPasswordResetService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminPasswordResetFlowTest extends TestCase
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

    public function test_request_existing_email_creates_token_and_sends_mail(): void
    {
        Mail::fake();

        AdminUser::query()->create([
            'username' => 'admin',
            'email' => 'admin@test.local',
            'password' => Hash::make('OldPass123!'),
            'is_active' => true,
            'is_super_admin' => true,
        ]);

        $response = $this->post('/admin/forgot-password', [
            'email' => 'admin@test.local',
        ]);

        $response->assertSessionHas('status');

        $this->assertDatabaseHas('admin_password_reset_tokens', [
            'email' => 'admin@test.local',
        ]);

        Mail::assertSent(AdminPasswordResetMail::class, 1);
    }

    public function test_request_unknown_email_keeps_generic_response(): void
    {
        Mail::fake();

        $response = $this->post('/admin/forgot-password', [
            'email' => 'unknown@test.local',
        ]);

        $response->assertSessionHas('status');
        $this->assertDatabaseMissing('admin_password_reset_tokens', [
            'email' => 'unknown@test.local',
        ]);

        Mail::assertNothingSent();
    }

    public function test_reset_password_consumes_token_and_rejects_reuse(): void
    {
        $admin = AdminUser::query()->create([
            'username' => 'admin',
            'email' => 'admin@test.local',
            'password' => Hash::make('OldPass123!'),
            'is_active' => true,
            'is_super_admin' => true,
        ]);

        $service = app(AdminPasswordResetService::class);
        $token = $service->requestReset('admin@test.local');

        $this->assertNotNull($token);

        $response = $this->post('/admin/reset-password', [
            'email' => 'admin@test.local',
            'token' => $token,
            'password' => 'NewPass1234!',
            'password_confirmation' => 'NewPass1234!',
        ]);

        $response->assertRedirect('/admin/login');

        $this->assertTrue(Hash::check('NewPass1234!', (string) $admin->fresh()->password));

        $this->assertNotNull(
            \DB::table('admin_password_reset_tokens')->where('email', 'admin@test.local')->value('used_at')
        );

        $second = $this->post('/admin/reset-password', [
            'email' => 'admin@test.local',
            'token' => $token,
            'password' => 'AnotherPass123!',
            'password_confirmation' => 'AnotherPass123!',
        ]);

        $second->assertSessionHasErrors();
    }

    public function test_expired_token_is_rejected(): void
    {
        AdminUser::query()->create([
            'username' => 'admin',
            'email' => 'admin@test.local',
            'password' => Hash::make('OldPass123!'),
            'is_active' => true,
            'is_super_admin' => true,
        ]);

        $service = app(AdminPasswordResetService::class);
        $token = (string) $service->requestReset('admin@test.local');

        \DB::table('admin_password_reset_tokens')
            ->where('email', 'admin@test.local')
            ->update(['created_at' => now()->subHours(2)]);

        $response = $this->post('/admin/reset-password', [
            'email' => 'admin@test.local',
            'token' => $token,
            'password' => 'NewPass1234!',
            'password_confirmation' => 'NewPass1234!',
        ]);

        $response->assertSessionHasErrors();
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
            $table->unsignedInteger('failed_login_attempts')->default(0);
            $table->timestamp('locked_until')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('admin_password_reset_tokens', function (Blueprint $table): void {
            $table->string('email')->primary();
            $table->string('token', 128);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->string('requested_ip', 45)->nullable();
            $table->string('used_ip', 45)->nullable();
        });
    }
}
