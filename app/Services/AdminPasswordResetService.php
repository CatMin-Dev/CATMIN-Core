<?php

namespace App\Services;

use App\Models\AdminUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminPasswordResetService
{
    public function requestReset(string $email, ?string $ip = null): ?string
    {
        $admin = AdminUser::query()
            ->where('email', $email)
            ->where('is_active', true)
            ->first();

        if (!$admin) {
            return null;
        }

        $this->purgeExpiredTokens();

        $token = Str::random(64);
        $hashedToken = hash('sha256', $token);

        DB::table('admin_password_reset_tokens')
            ->updateOrInsert(
                ['email' => $admin->email],
                [
                    'token' => $hashedToken,
                    'created_at' => now(),
                    'used_at' => null,
                    'requested_ip' => $ip,
                ]
            );

        return $token;
    }

    public function isValidToken(string $email, string $token): bool
    {
        $row = DB::table('admin_password_reset_tokens')
            ->where('email', $email)
            ->first();

        if (!$row) {
            return false;
        }

        if ($row->used_at !== null) {
            return false;
        }

        $expiresIn = (int) config('catmin.admin.password_reset_expire_minutes', 60);
        if (now()->diffInMinutes($row->created_at) > $expiresIn) {
            return false;
        }

        return hash_equals((string) $row->token, hash('sha256', $token));
    }

    public function resetPassword(string $email, string $token, string $newPassword, ?string $ip = null): bool
    {
        if (!$this->isValidToken($email, $token)) {
            return false;
        }

        $admin = AdminUser::query()
            ->where('email', $email)
            ->where('is_active', true)
            ->first();

        if (!$admin) {
            return false;
        }

        $admin->password = Hash::make($newPassword);
        $admin->failed_login_attempts = 0;
        $admin->locked_until = null;
        $admin->save();

        DB::table('admin_password_reset_tokens')
            ->where('email', $email)
            ->update([
                'used_at' => now(),
                'used_ip' => $ip,
            ]);

        return true;
    }

    public function purgeExpiredTokens(): void
    {
        $expiresIn = (int) config('catmin.admin.password_reset_expire_minutes', 60);

        DB::table('admin_password_reset_tokens')
            ->where('created_at', '<', now()->subMinutes($expiresIn))
            ->delete();
    }
}
