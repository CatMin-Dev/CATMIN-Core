<?php

namespace App\Services;

use App\Models\AdminUser;

class ProfileExtensionResolverService
{
    /**
     * @return array<string, mixed>
     */
    public function forAdminUser(int $adminUserId): array
    {
        $adminUser = AdminUser::query()->find($adminUserId);

        $fallback = [
            'phone' => $adminUser?->phone,
            'mobile' => null,
            'company_name' => null,
            'address_line_1' => null,
            'address_line_2' => null,
            'postal_code' => null,
            'city' => null,
            'state' => null,
            'country_code' => null,
            'identity_type' => null,
            'identity_number' => null,
            'preferred_contact_method' => 'email',
            'contact_opt_in' => false,
            'resolved_from' => 'fallback',
        ];

        if (!$this->isAddonEnabled()) {
            return $fallback;
        }

        if (!class_exists(\Addons\CatminProfileExtensions\Services\ProfileExtensionService::class)) {
            return $fallback;
        }

        try {
            /** @var \Addons\CatminProfileExtensions\Services\ProfileExtensionService $service */
            $service = app(\Addons\CatminProfileExtensions\Services\ProfileExtensionService::class);
            $profile = $service->toArrayForAdminUser($adminUserId);

            return array_merge($fallback, $profile, ['resolved_from' => 'profile_extension']);
        } catch (\Throwable) {
            return $fallback;
        }
    }

    public function contactPhoneForAdmin(int $adminUserId): ?string
    {
        $data = $this->forAdminUser($adminUserId);

        $preferred = $data['preferred_contact_method'] ?? null;

        if ($preferred === 'mobile' && !empty($data['mobile'])) {
            return (string) $data['mobile'];
        }

        if ($preferred === 'phone' && !empty($data['phone'])) {
            return (string) $data['phone'];
        }

        return (string) ($data['mobile'] ?: $data['phone'] ?: '') ?: null;
    }

    /**
     * @return array<string, string|null>
     */
    public function billingAddressForAdmin(int $adminUserId): array
    {
        $data = $this->forAdminUser($adminUserId);

        return [
            'company_name' => $data['company_name'] ?: null,
            'address_line_1' => $data['address_line_1'] ?: null,
            'address_line_2' => $data['address_line_2'] ?: null,
            'postal_code' => $data['postal_code'] ?: null,
            'city' => $data['city'] ?: null,
            'state' => $data['state'] ?: null,
            'country_code' => $data['country_code'] ?: null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function contactPreferencesForAdmin(int $adminUserId): array
    {
        $data = $this->forAdminUser($adminUserId);

        return [
            'preferred_contact_method' => $data['preferred_contact_method'] ?: 'email',
            'contact_opt_in' => (bool) ($data['contact_opt_in'] ?? false),
        ];
    }

    public function isAddonEnabled(): bool
    {
        $forced = config('catmin.profile_extensions.enabled');
        if ($forced === false) {
            return false;
        }

        try {
            return AddonManager::enabled()->contains(function ($addon): bool {
                return (string) ($addon->slug ?? '') === 'catmin-profile-extensions';
            });
        } catch (\Throwable) {
            return false;
        }
    }
}
