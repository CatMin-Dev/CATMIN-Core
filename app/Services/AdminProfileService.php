<?php

namespace App\Services;

use App\Models\AdminUser;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Modules\Logger\Services\SystemLogService;

class AdminProfileService
{
    /**
     * @param array<string, mixed> $payload
     */
    public function updateProfile(AdminUser $adminUser, array $payload): AdminUser
    {
        $adminUser->first_name = $this->nullableString($payload['first_name'] ?? null);
        $adminUser->last_name = $this->nullableString($payload['last_name'] ?? null);
        $adminUser->contact_email = $this->nullableString($payload['contact_email'] ?? null);
        $adminUser->phone = $this->nullableString($payload['phone'] ?? null);
        $adminUser->save();

        $this->audit('admin.profile.updated', 'Profil admin mis a jour', [
            'admin_user_id' => $adminUser->id,
            'is_super_admin' => (bool) $adminUser->is_super_admin,
        ]);

        return $adminUser;
    }

    public function updateAvatar(AdminUser $adminUser, ?int $avatarMediaAssetId): AdminUser
    {
        $adminUser->avatar_media_asset_id = $avatarMediaAssetId;
        $adminUser->save();

        $this->audit('admin.profile.avatar.updated', 'Avatar admin mis a jour', [
            'admin_user_id' => $adminUser->id,
            'avatar_media_asset_id' => $avatarMediaAssetId,
        ]);

        return $adminUser;
    }

    public function changePassword(AdminUser $adminUser, string $currentPassword, string $newPassword): bool
    {
        if (!Hash::check($currentPassword, (string) $adminUser->password)) {
            return false;
        }

        $adminUser->password = Hash::make($newPassword);
        $adminUser->failed_login_attempts = 0;
        $adminUser->locked_until = null;
        $adminUser->save();

        $this->audit('admin.profile.password.changed', 'Mot de passe admin modifie', [
            'admin_user_id' => $adminUser->id,
            'is_super_admin' => (bool) $adminUser->is_super_admin,
        ]);

        return true;
    }

    public function mediaTableExists(): bool
    {
        try {
            return Schema::hasTable('media_assets');
        } catch (\Throwable) {
            return false;
        }
    }

    private function nullableString(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : '';

        return $value === '' ? null : $value;
    }

    /**
     * @param array<string, mixed> $context
     */
    private function audit(string $event, string $message, array $context): void
    {
        try {
            app(SystemLogService::class)->logAudit(
                $event,
                $message,
                $context,
                'info',
                (string) session('catmin_admin_username', '')
            );
        } catch (\Throwable) {
        }
    }
}
