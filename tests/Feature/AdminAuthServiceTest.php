<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Services\AdminAuthService;
use Tests\TestCase;

class AdminAuthServiceTest extends TestCase
{
    protected AdminAuthService $authService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authService = app(AdminAuthService::class);
    }

    public function test_successful_auth_with_correct_credentials(): void
    {
        $admin = AdminUser::query()->where('username', 'admin')->firstOrFail();

        // Test with correct password (even hashed, we know the legacy one)
        $result = $this->authService->attempt('admin', 'admin12345');

        $this->assertTrue($result['success']);
        $this->assertNotNull($result['user']);
        $this->assertEquals('admin', $result['user']->username);
        $this->assertNull($result['error']);
    }

    public function test_failed_auth_with_wrong_password(): void
    {
        $result = $this->authService->attempt('admin', 'wrongpassword');

        $this->assertFalse($result['success']);
        $this->assertNull($result['user']);
        $this->assertEquals('Identifiants invalides.', $result['error']);
    }

    public function test_failed_auth_with_nonexistent_user(): void
    {
        $result = $this->authService->attempt('nonexistent', 'password123');

        $this->assertFalse($result['success']);
        $this->assertNull($result['user']);
        $this->assertNotNull($result['error']);
    }

    public function test_inactive_admin_cannot_login(): void
    {
        $admin = AdminUser::create([
            'username' => 'inactive_admin',
            'email' => 'inactive@test.local',
            'password' => bcrypt('password123'),
            'is_active' => false,
        ]);

        $result = $this->authService->attempt('inactive_admin', 'password123');

        $this->assertFalse($result['success']);
        $this->assertEquals('Compte désactivé.', $result['error']);

        $admin->forceDelete();
    }

    public function test_locked_account_cannot_login(): void
    {
        $admin = AdminUser::create([
            'username' => 'locked_admin',
            'email' => 'locked@test.local',
            'password' => bcrypt('password123'),
            'failed_login_attempts' => 5,
            'locked_until' => now()->addHours(1),
        ]);

        $result = $this->authService->attempt('locked_admin', 'password123');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('verrouillé', strtolower($result['error']));

        $admin->forceDelete();
    }

    public function test_failed_attempt_increments_counter(): void
    {
        $admin = AdminUser::create([
            'username' => 'test_attempts',
            'email' => 'test_attempts@local',
            'password' => bcrypt('correct123'),
            'failed_login_attempts' => 0,
        ]);

        for ($i = 0; $i < 3; $i++) {
            $this->authService->attempt('test_attempts', 'wrong');
        }

        $admin->refresh();
        $this->assertEquals(3, $admin->failed_login_attempts);

        $admin->forceDelete();
    }

    public function test_account_locks_after_5_failed_attempts(): void
    {
        $admin = AdminUser::create([
            'username' => 'test_lock',
            'email' => 'test_lock@local',
            'password' => bcrypt('correct123'),
            'failed_login_attempts' => 0,
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->authService->attempt('test_lock', 'wrong');
        }

        $admin->refresh();
        $this->assertEquals(5, $admin->failed_login_attempts);
        $this->assertNotNull($admin->locked_until);

        $admin->forceDelete();
    }

    public function test_successful_login_clears_failed_attempts(): void
    {
        $admin = AdminUser::create([
            'username' => 'test_clear',
            'email' => 'test_clear@local',
            'password' => bcrypt('goodpass'),
            'failed_login_attempts' => 3,
        ]);

        $result = $this->authService->attempt('test_clear', 'goodpass');

        $this->assertTrue($result['success']);

        $admin->refresh();
        $this->assertEquals(0, $admin->failed_login_attempts);
        $this->assertNull($admin->locked_until);
        $this->assertNotNull($admin->last_login_at);

        $admin->forceDelete();
    }
}
