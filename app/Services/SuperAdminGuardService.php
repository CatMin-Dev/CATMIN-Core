<?php

namespace App\Services;

use App\Models\AdminUser;

class SuperAdminGuardService
{
    public function isSuperAdmin(AdminUser $adminUser): bool
    {
        return (bool) $adminUser->is_super_admin;
    }

    public function activeSuperAdminsCount(): int
    {
        return AdminUser::query()
            ->where('is_super_admin', true)
            ->where('is_active', true)
            ->count();
    }

    /**
     * @return array{allowed: bool, reason: string}
     */
    public function canDelete(AdminUser $target): array
    {
        if (!$this->isSuperAdmin($target)) {
            return ['allowed' => true, 'reason' => 'ok'];
        }

        if ($this->activeSuperAdminsCount() <= 1) {
            return [
                'allowed' => false,
                'reason' => 'Impossible de supprimer le dernier super-admin actif.',
            ];
        }

        return ['allowed' => true, 'reason' => 'ok'];
    }

    /**
     * @return array{allowed: bool, reason: string}
     */
    public function canDeactivate(AdminUser $target, bool $nextIsActive): array
    {
        if (!$this->isSuperAdmin($target)) {
            return ['allowed' => true, 'reason' => 'ok'];
        }

        if ($nextIsActive) {
            return ['allowed' => true, 'reason' => 'ok'];
        }

        if ($this->activeSuperAdminsCount() <= 1) {
            return [
                'allowed' => false,
                'reason' => 'Impossible de desactiver le dernier super-admin actif.',
            ];
        }

        return ['allowed' => true, 'reason' => 'ok'];
    }

    /**
     * @return array{allowed: bool, reason: string}
     */
    public function canDemote(AdminUser $target, bool $nextIsSuperAdmin): array
    {
        if (!$this->isSuperAdmin($target)) {
            return ['allowed' => true, 'reason' => 'ok'];
        }

        if ($nextIsSuperAdmin) {
            return ['allowed' => true, 'reason' => 'ok'];
        }

        if ($target->is_active && $this->activeSuperAdminsCount() <= 1) {
            return [
                'allowed' => false,
                'reason' => 'Impossible de retirer le statut du dernier super-admin actif.',
            ];
        }

        return ['allowed' => true, 'reason' => 'ok'];
    }
}
