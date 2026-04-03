<?php

namespace Addons\CatminProfileExtensions\Services;

use Addons\CatminProfileExtensions\Models\UserProfileExtended;
use Illuminate\Support\Facades\Schema;

class ProfileExtensionService
{
    /**
     * @param array<string, mixed> $payload
     */
    public function upsertForAdminUser(int $adminUserId, array $payload): ?UserProfileExtended
    {
        if (!$this->tableExists()) {
            return null;
        }

        $profile = UserProfileExtended::query()->firstOrNew(['admin_user_id' => $adminUserId]);

        $profile->phone = $this->nullableString($payload['phone'] ?? null);
        $profile->mobile = $this->nullableString($payload['mobile'] ?? null);
        $profile->company_name = $this->nullableString($payload['company_name'] ?? null);
        $profile->address_line_1 = $this->nullableString($payload['address_line_1'] ?? null);
        $profile->address_line_2 = $this->nullableString($payload['address_line_2'] ?? null);
        $profile->postal_code = $this->nullableString($payload['postal_code'] ?? null);
        $profile->city = $this->nullableString($payload['city'] ?? null);
        $profile->state = $this->nullableString($payload['state'] ?? null);
        $profile->country_code = $this->normalizeCountryCode($payload['country_code'] ?? null);
        $profile->identity_type = $this->nullableString($payload['identity_type'] ?? null);
        $profile->identity_number = $this->nullableString($payload['identity_number'] ?? null);
        $profile->preferred_contact_method = $this->nullableString($payload['preferred_contact_method'] ?? null);
        $profile->contact_opt_in = (bool) ($payload['contact_opt_in'] ?? false);

        $profile->save();

        return $profile;
    }

    public function forAdminUser(int $adminUserId): ?UserProfileExtended
    {
        if (!$this->tableExists()) {
            return null;
        }

        return UserProfileExtended::query()->where('admin_user_id', $adminUserId)->first();
    }

    /**
     * @return array<string, mixed>
     */
    public function toArrayForAdminUser(int $adminUserId): array
    {
        $profile = $this->forAdminUser($adminUserId);

        return [
            'phone' => $profile?->phone,
            'mobile' => $profile?->mobile,
            'company_name' => $profile?->company_name,
            'address_line_1' => $profile?->address_line_1,
            'address_line_2' => $profile?->address_line_2,
            'postal_code' => $profile?->postal_code,
            'city' => $profile?->city,
            'state' => $profile?->state,
            'country_code' => $profile?->country_code,
            'identity_type' => $profile?->identity_type,
            'identity_number' => $profile?->identity_number,
            'preferred_contact_method' => $profile?->preferred_contact_method,
            'contact_opt_in' => (bool) ($profile?->contact_opt_in ?? false),
        ];
    }

    public function tableExists(): bool
    {
        try {
            return Schema::hasTable('user_profiles_extended');
        } catch (\Throwable) {
            return false;
        }
    }

    private function nullableString(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : '';

        return $value === '' ? null : $value;
    }

    private function normalizeCountryCode(mixed $value): ?string
    {
        $value = $this->nullableString($value);

        if ($value === null) {
            return null;
        }

        return strtoupper(substr($value, 0, 2));
    }
}
